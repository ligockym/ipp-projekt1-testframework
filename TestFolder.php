<?php

class TestFolder
{
    /**
     * @var TestFolder[]
     */
    private array $subfolders;

    /**
     * @var TestItem[] might be an empty array if this folder does not contain any tests (might contain subfolders)
     */
    private array $tests;

    private string $path;

    private bool $recursive;

    private int $correct_tests;

    private int $total_tests;

    private bool $is_correct;

    /**
     * Test folder is responsible for holding test items and subfolders.
     * When tests are supposed to run recursively, subfolders are also being tested
     * @param TestFolder[] $subfolder
     */
    public function __construct(string $path, bool $recursive)
    {
        $this->path = $path;
        $this->recursive = $recursive;

        $this->total_tests = -1;
        $this->correct_tests = -1;
        $this->is_correct = true;

        $this->tests = [];
        $this->subfolders = [];

        $this->split_subfolders_and_tests();
    }

    /**
     * Finds subfolders and .src files in current folder and set $this->tests and $this->subfolders.
     * If recursive mode is not enabled, then subfolders remains empty
     */
    private function split_subfolders_and_tests(): void
    {
        $dir_content = $this->get_content_of_folder();

        // loop through all directory content and split into subfolders and tests
        foreach ($dir_content as $item) {
            $item_path = $this->path . '/' . $item;

            if (is_dir($item_path)) {
                // is directory -> add to subfolders
                if ($this->recursive) {
                    $this->subfolders[] = new TestFolder($item_path, $this->recursive);
                }
            } else { // is a file
                // check if file ends with .src
                if (preg_match('/^(.*).src$/', $item_path, $matches)) {
                    $path_without_ending = $matches[1];

                    $this->tests[] = new TestItem($path_without_ending);
                }
            }
        }
    }

    /**
     * Calculates number of tests in this folder including all tests in subfolders.
     * Beware that if recursive mode is not enabled, subfolders are empty.
     * @return array
     */
    public function calculate_number_of_tests(): array
    {
        // not calculated
        if ($this->total_tests === -1) {
            $this->total_tests = 0;
            $this->correct_tests = 0;

            // calculate number of tests, correct test and if folder is correct (all subtests have to be correct)
            foreach ($this->tests as $test) {
                $this->total_tests++;
                if ($test->is_correct()) {
                    $this->correct_tests++;
                }
                $this->is_correct = $this->is_correct && $test->is_correct();
            }

            // add number of tests and correct tests from subfolders
            foreach ($this->getSubfolders() as $subfolder) {
                $subfolder_result = $subfolder->calculate_number_of_tests();
                $this->total_tests += $subfolder_result['total_tests'];
                $this->correct_tests += $subfolder_result['correct_tests'];
                $this->is_correct = $subfolder_result['is_correct'] && $this->is_correct;
            }
        }
        return ['total_tests' => $this->total_tests, 'correct_tests' => $this->correct_tests, 'is_correct' => $this->is_correct];
    }

    private function get_content_of_folder()
    {
        if (!is_dir($this->path)) {
            // path not found
            exit(ERR_PATH_NOT_FOUND);
        }

        return array_diff(scandir($this->path), ['.', '..']);
    }

    /**
     * Runs tests for every test in this folder and also in subfolders.
     * @param $parse_script
     * @param $int_script
     */
    public function run_tests($parse_script, $int_script)
    {
        // run tests for every test file
        foreach ($this->tests as $test) {
            $test->generate_missing_files();
            $test->test($parse_script, $int_script);
        }

        // recursively run tests on subfolders
        if ($this->recursive) {
            foreach ($this->subfolders as $subfolder) {
                $subfolder->run_tests($parse_script, $int_script);
            }
        }
    }

    public function clean()
    {
        // run tests for every test file
        foreach ($this->tests as $test) {
            $test->clean_after_parser();
            $test->clean_after_interpret();
        }

        // recursively run tests on subfolders
        if ($this->recursive) {
            foreach ($this->subfolders as $subfolder) {
                $subfolder->clean();
            }
        }
    }

    /**
     * @return TestFolder[]
     */
    public function getSubfolders(): array
    {
        return $this->subfolders;
    }

    /**
     * @return TestItem[]
     */
    public function get_tests(): array
    {
        return $this->tests;
    }

    public function is_correct(): bool
    {
        return $this->is_correct;
    }

    public function get_path(): string
    {
        return $this->path;
    }

    public function get_total_tests(): int
    {
        return $this->total_tests;
    }

    public function get_correct_tests(): int
    {
        return $this->correct_tests;
    }
}