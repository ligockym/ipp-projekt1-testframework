<?php

ini_set('display_errors', 'stderr');

spl_autoload_register(function ($class_name) {
    if (!class_exists($class_name)) {
        include_once $class_name . '.php';
    }
});

const ERR_PATH_NOT_FOUND = 41;

$directory = '.';
$recursive = false;

$parse_script = 'parse.php';
$parse_script_set = false; // set to true when parameter changes it

$int_script = 'interpret.py';
$int_script_set = false; // set to true when parameter changes it

$parse_only = false;
$int_only = false;

$jexampath = '/pub/courses/ipp/jxamxml/';
$no_clean = false;

// arguments from command line
for ($i = 1; $i < $argc; $i++) {
    // --help command
    if (isset($argv[$i])) {
        if ($argv[$i] == '--help') {
            echo 'Script test.php is used for automatic testing of parse.php and interpret.php. Script will run all tests found in the directory (use --directory to specify) and generate HTML result to standard output.

Parameters: 
    • [--help] prints help for program
    • [--directory=path] directory of tests, if parameter is missing, this directory is used
    • [--recursive] tests will be searched also in subdirectorys
    • [--parse-script=file] file with script for analysis of source code (default is ./parse.php)
    • [--int-script=file] file with script for interpret of XML code (default ./interpret.py) 
    • [--parse-only] only script for analysis of source code will be tested
    • [--int-only] only script for interpretation of source code will be tested
    • [--jexampath=path] path to directory including jxamxml.jar and options (default is /pub/cources/ipp/jexamxml/)
    • [--noclean] temporary files with results will not be removed 

Error codes: 
    • 41 - directory of file not found or cannot be accessed (for all parameters with path/file)
';
            exit(0);
        } else if (preg_match('/^--directory=(.*)$/', $argv[$i], $matches)) {
            $directory = $matches[1] ?? '.';
        } else if ($argv[$i] == '--recursive') {
            $recursive = true;
        } else if (preg_match('/^--parse-script=(.*)$/', $argv[$i], $matches)) {
            $parse_script = $matches[1];
            $parse_script_set = true;
        } else if (preg_match('/^--int-script=(.*)$/', $argv[$i], $matches)) {
            $int_script = $matches[1];
            $int_script_set = true;
        } else if (preg_match('/^--parse-only$/', $argv[$i], $matches)) {
            $parse_only = true;
        } else if (preg_match('/^--int-only$/', $argv[$i], $matches)) {
            $int_only = true;
        } else if (preg_match('/^--jexampath=(.*)$/', $argv[$i], $matches)) {
            // add / to the end of string (remove and add)
            $jexampath = rtrim($matches[1], '/') . '/';
        } else if (preg_match('/^--noclean$/', $argv[$i], $matches)) {
            $no_clean = true;
        }
    }
}

// check for non-allowed combinations
$condition_parse_only = $parse_only && ($int_only || $int_script_set);
$condition_int_only = $int_only && ($parse_only || $parse_script_set);

if ($condition_int_only || $condition_parse_only) {
    exit(1); // not allowed
}

if ($int_only) $parse_script = null;
if ($parse_only) $int_script = null;

// test if parser path exists and interpreter path exists
if ($int_script && !is_file($int_script) || ($parse_script && !is_file($parse_script))) {
    exit(ERR_PATH_NOT_FOUND);
}

$root_folder = new TestFolder($directory, $recursive);
$root_folder->run_tests($parse_script, $int_script);
$root_folder->calculate_number_of_tests();

function generate_html(TestFolder $folder) {
    ob_start();
    /**
     * @var $tests array is used here
     */
    include 'template.php';
    $buffer = ob_get_clean();
    echo $buffer;
}

// Generate html
generate_html($root_folder);

if (!$no_clean) {
    $root_folder->clean();
}
