<?php

class TestItem
{
    private string $path;
    private string $src_path;
    private string $input_path; // for interpret
    private string $parser_output_path;
    private string $ret_code_path;

    private bool $is_correct;
    private bool $is_correct_ret_code;
    private string $xml_delta_path;
    private int $ret_code;
    private int $reference_ret_code;
    private string $interpret_output_path;
    private string $reference_output_path;
    private string $interpret_delta;

    /**
     * Test item is responsible for running a test identified by one .src file.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        $this->src_path = $this->path . '.src';
        $this->input_path = $this->path . '.in';
        $this->reference_output_path = $this->path . '.out';
        $this->parser_output_path = $this->path . '.parser.out';
        $this->interpret_output_path = $this->path . '.inter.out';
        $this->ret_code_path = $this->path . '.code';
        $this->xml_delta_path = $this->path . '.delta.xml';
        $this->interpret_delta = "";
        $this->is_correct_ret_code = false;
        $this->ret_code = 0;
        $this->reference_ret_code = 0;
        $this->is_correct = false;
    }

    /**
     * Runs testing, either only parser/interpreter or both.
     * @param string|null $parser_path
     * @param string|null $interpreter_path
     * @return bool whether test was successful
     */
    public function test(?string $parser_path = null, ?string $interpreter_path = null): bool
    {
        if ($parser_path) {
            $this->run_parser($parser_path);
        }
        // run interpret only if return code of parser was 0 or parser did not run (ret_code initial value is also 0)
        if ($interpreter_path && $this->ret_code === 0) {
            $this->run_interpret($interpreter_path, (bool) $parser_path);
        }
        $is_code_same = $this->test_return_code();

        // test content only if return code is 0
        if ($is_code_same && $this->ret_code === 0) {
            if ($interpreter_path) {
                // both parser&interpret or only interpret
                $is_output_same = $this->test_output_interpret();
            } else {
                // only parser
                $is_output_same = $this->test_output_parser();
            }
        } else {
            $is_output_same = true; // when not 0, content is not important
        }

        $this->is_correct = $is_code_same && $is_output_same;
        return $this->is_correct;
    }

    private function run_parser($parser_path): void
    {

        exec("php8.1 $parser_path < $this->src_path > $this->parser_output_path", $output, $exec_ret_code);
        file_put_contents($this->ret_code_path, $exec_ret_code);
    }

    private function run_interpret($interpret_path, $was_parser): void
    {
        $source_file = $was_parser ? $this->parser_output_path : $this->src_path;

        exec("python3.8 $interpret_path  --source=$source_file --input=$this->input_path > $this->interpret_output_path", $output, $exec_ret_code);
        file_put_contents($this->ret_code_path, $exec_ret_code);
    }

    public function clean_after_parser(): void
    {
        exec("rm -f $this->parser_output_path && rm -f $this->ret_code_path && rm -f $this->xml_delta_path");
    }

    public function clean_after_interpret(): void {
        exec("rm -f $this->interpret_output_path && rm -f $this->ret_code_path");
    }

    private function test_return_code(): bool
    {
        $reference_path = $this->path . '.rc';

        // load reference return code
        $this->reference_ret_code = file_get_contents($reference_path);
        $this->ret_code = file_get_contents($this->ret_code_path);

        $this->is_correct_ret_code = $this->reference_ret_code === $this->ret_code;
        return $this->is_correct_ret_code;
    }

    private function test_output_parser(): bool
    {
        exec("java -jar jexamxml/jexamxml.jar $this->parser_output_path {$this->reference_output_path} $this->xml_delta_path jexamxml/options", $output, $res_code);

        return $res_code === 0;
    }

    private function test_output_interpret(): bool {
        exec("diff $this->reference_output_path $this->interpret_output_path 2>&1", $output, $exec_res_code);
        if (is_array($output)) {
            $this->interpret_delta = join("\n", $output);
        }
        return $exec_res_code === 0;
    }

    /**
     * Files .rc, .out, .in do not have to exist. Therefore, default content is used.
     */
    public function generate_missing_files()
    {
        $reference_output = $this->reference_output_path;
        $reference_code = $this->path . '.rc';
        $in = $this->path . '.in';

        if (!is_file($reference_output)) {
            file_put_contents($reference_output, '');
        }
        if (!is_file($in)) {
            file_put_contents($in, '');
        }
        if (!is_file($reference_code)) {
            file_put_contents($reference_code, '0');
        }
    }

    public function get_path()
    {
        return $this->path;
    }

    public function is_correct()
    {
        return $this->is_correct;
    }

    public function get_result_delta()
    {
        if (is_file($this->xml_delta_path)) {
            return file_get_contents($this->xml_delta_path);
        } elseif ($this->interpret_delta) {
            return $this->interpret_delta;
        }
        return "";
    }

    public function get_ret_code()
    {
        return $this->ret_code;
    }

    public function is_correct_ret_code(): bool
    {
        return $this->is_correct_ret_code;
    }

    public function get_reference_ret_code() {
        return $this->reference_ret_code;
    }
}