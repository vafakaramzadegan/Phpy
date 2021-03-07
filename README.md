# Phpy
Phpy lets you simply execute Python script files from your PHP code and read the output.

## Installation
Please note that Phpy does not currently support Microsoft Windows.

### Install using composer
Phpy can be installed using composer:

`$ composer require vafakaramzadegan/phpy`

just include the autoloader after installation:

```php
<?php

require("vendor/autoload.php");

use Vafakaramzadegan\Phpy;

```

### Manual installation
You can also use Phpy without composer:
```php
<?php

require("Phpy.php");

# you can also remove namespace in Phpy.php file
use Vafakaramzadegan\Phpy;

```

## Usage
Using Phpy is easy. set scripts directory, select your Python script file, and pass the arguments if present:

```php
$py = new Phpy();

$py->set_python_scripts_dir("/path/to/your/python/scripts")->
     execute("python_script_filename_without_extension", ["array", "of", "arguments"]);
```

Assuming that the directory `/home/YourUserName/Documents/python_scripts` exists on your computer, the `www-data` user must have access to `read` and `execute` python scripts inside the directory. The permissions have to be set manually.

## Simple example
start by creating a python file inside the directory. let's call it `test_python.py`.

paste the following code into the file:

```python
import sys

print("You passed following args:")
for index, arg in enumerate(sys.argv[1:]):
    print(index+1, arg)
```

now, in your PHP script:

```php
$py = new Phpy();

echo print_r(
    $py->set_python_scripts_dir("/home/YourUserName/Documents/python_scripts")->
    execute("test_python", ["arg1", "arg2", "arg3"])
);
```

The result would be:
```html
Array
(
    [0] => You passed following args:
    [1] => 1 arg1
    [2] => 2 arg2
    [3] => 3 arg3
)
1
```

## Options
You can have extra control over how your python script is executed. Phpy provides methods and options to meet your demands.

### Select Python version
You may select the desired Python version.
```php
   // Python2 - probably shipped with your OS
   $py->set_python_version(2);
   // Python3 - should be installed prior to use
   $py->set_python_version(3);
```
### Asynchronous execution
by default, Phpy executes python scripts synchronously, which means that PHP waits for your python script to finish execution.

however, Phpy has the ability to execute scripts in the background. 
all you have to do is:

```php
echo $py->
    // the path to python scripts
    set_python_scripts_dir("/home/YourUserName/Documents/python_scripts")
    // the directory where the output of python script will be stored after execution finished
    set_output_dir("/home/YourUserName/Documents/python_scripts/output")
    // keep the script running after the execution of php script is finished
    run_in_background(true)->
    execute("test_python", ["arg1", "arg2", "arg3"]);
```

whenever a Python file is set to run in the background, the PHP script does not wait for it to complete. instead, `execute()` command returns a unique identifier number like `1612798795670`.
you can store this number in your database or session, and use it later to retrieve the output:
```php
$py = new Phpy();
echo print_r($py->get_exec_result(1612798795670));
```

The output of the above code would be:
```html
Array
(
    [0] => You passed following args:
    [1] => 1 arg1
    [2] => 2 arg2
    [3] => 3 arg3
)
1
```
The outputs are cached on disk. so make sure to empty the cache periodically:
```php
$py->
# output directory must be set
set_output_dir("/home/YourUserName/Documents/python_scripts/output")->
// delete cache files prior to an hour ago
flush_outputs(3600);
```
