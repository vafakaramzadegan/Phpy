# Phpy
Phpy allows you to simply execute Python scripts from your PHP code and read the output.

## Usage
Please note that Phpy does not currently support Microsoft Windows.

First, install Phpy using composer:

`$ composer require vafakaramzadegan/phpy`

and then include it in your php script:

```php
<?php

require("vendor/autoload.php");

use Vafakaramzadegan\Phpy;

```

You can execute your python scripts now:

```php
$py = new Phpy();

$py->set_python_scripts_dir("/path/to/your/python/scripts")->
     execute("python_script_filename_without_extension", ["array", "of", "arguments"]);
```

Assuming that the directory `/home/YourUserName/Documents/python_scripts` exists on your computer, the `www-data` user must have access to read and execute python scripts inside it.
now, create a python file inside the directory. let's call it `test_python.py` and paste the following code inside it:

```python
import sys

print("You passed following args:")
for index, arg in enumerate(sys.argv[1:]):
    print(index+1, arg)
```

now, in your PHP script:

```php
require("vendor/autoload.php");

use Vafakaramzadegan\Phpy;

$py = new Phpy();

echo print_r($py->set_python_scripts_dir("/home/YourUserName/Documents/python_scripts")->
     execute("test_python", ["arg1", "arg2", "arg3"]));
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
You can have extra control on how your python scripts are executed.
by default, Phpy executes python scripts synchronously, but it has the ability to execute scripts in the background.
all you have to do is:

```php
echo $py->
    // the path to python scripts
    set_python_scripts_dir("/home/YourUserName/Documents/python_scripts")
    // the directory where the output of python script will be stored after execution finished
    set_nohup_output_dir("/home/YourUserName/Documents/python_scripts/output")
    // keep the script running after the execution of php script is finished
    run_in_background(true)->
    execute("test_python", ["arg1", "arg2", "arg3"]);
```

whenever a script is set to run in the background, the result of `execute()` becomes a unique identifier number. 
for example: `1612798795670`.

you can store this number in your database or in session, and use it to retrieve the output:
```php
$py = new Phpy();
echo $py->get_exec_result(1612798795670);
```

The output would be:
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
