<?php

namespace Vafakaramzadegan;

/**
 * Phpy
 * -----------------------------
 * Phpy allows you to simply execute Python scripts from your
 * PHP code and read the output.
 * 
 * @author: Vafa karamzadegan
 */
class Phpy{
    // the python command to be used inside terminal("python2" or "python3")
    private $python_command = "";
    private $pyhton_scripts_dir = "";
    /**
     * overrides the default encoding for stdin/stdout/stderr.
     * PYTHONIOENCODING should be set before running the interpreter. 
     * default value is set to unicode.
     */
    private $encoding = "utf8";
    /**
     * python scripts can also be executed in the background,
     * so your php script does not have to wait for them to finish.
     * i used "nohup" for this. nohup tells the system not to stop
     * the command once it has started. so it'll keep running until
     * it's done.
     */
    private $run_in_bg = false;
    private $nohup_out_dir = "";
    private $nohup_out_id = 0;
    private $nohup_out_ext = "out";

    public function __construct(){
        // windows is not currently supported.
        if (DIRECTORY_SEPARATOR != "/")
            throw new \Exception("Oh no... Windows! :(", 1);

        // "python3" command will be used to execute scripts.
        $this->set_python_version(3);

        /**
         * the default directory to store nohup outputs.
         * MAKE SURE the directory EXISTS and "www-data" HAS PERMISSION to
         * WRITE in it.
         */
        $this->nohup_out_dir = "";
    }

    /**
     * set python scripts directory
     *
     * MAKE SURE "www-data" HAS PERMISSION to ACCESS and EXECUTE
     * python scripts in the directory.
     *
     * @access public
     * @param string $dir
     * @return object Phpy
     */
    public function set_python_scripts_dir($dir){
        $this->pyhton_scripts_dir = $dir;

        return $this;
    }

    /**
     * set nohup output directory
     *
     * MAKE SURE "www-data" HAS PERMISSION to ACCESS and WRITE
     * to the directory.
     *
     * @access public
     * @param string $dir
     * @return object Phpy
     */
    public function set_output_dir($dir){
        $this->nohup_out_dir = $dir;

        return $this;
    }

    /**
     * select a desired python interpreter version
     *
     * @access public
     * @param int $version
     * @return object Phpy
     */
    public function set_python_version($version){
        switch ($version) {
            case 2:
                $this->python_command = "python2";
                break;
            case 3:
                $this->python_command = "python3";
                break;
            default:
                throw new \Exception("Invalid python version selected!", 1);
                break;
        }

        return $this;
    }

    /**
     * delete nohup output files
     *
     * files older than $timeago will be deleted
     *
     * @access public
     * @param int $timeago in seconds
     * @return object Phpy
     */
    public function flush_outputs($timeago=0){
        $files = glob("{$this->nohup_out_dir}/*.{$this->nohup_out_ext}");
        foreach ($files as $file)
            if (filemtime($file) < time() - $timeago)
                unlink($file);

        return $this;
    }

    /**
     * set whether the script should execute in the background
     *
     * @access public
     * @param bool $state
     * @return object Phpy
     */
    public function run_in_background($state){
        $this->run_in_bg = $state;

        return $this;
    }

    /**
     * get script execution id
     *
     * @access public
     * @return int nohup_out_id
     */
    public function get_exec_id(){
        if (!$this->run_in_bg)
            throw new \Exception("Script is not set to run in background!", 1);
            
        return $this->nohup_out_id;
    }

    /**
     * get the output of a script that ran in the background
     *
     * returns false if the output file is not found.
     *
     * @access public
     * @param int $id
     * @return string output of the python script
     */
    public function get_exec_result($id){
        $fn = "{$this->nohup_out_dir}/$id.{$this->nohup_out_ext}";
        if (!file_exists($fn))
            return false;

        return file_get_contents($fn);
    }

    /**
     * execute the script
     *
     * @access public
     * @param string $filename
     * @param array $params
     * @return int $this->nohup_out_id | string output of the python script
     */
    public function execute($filename, $params=[]){
        // check if python scripts directory is set
        if (!file_exists($this->pyhton_scripts_dir))
            throw new \Exception("Python script path is not set!", 1);
        
        // check if nohup output directory is set
        if ($this->run_in_bg)
            if (!file_exists($this->nohup_out_dir))
                throw new \Exception("Nohup output directory is not set!", 1);
        
        // check if filename is provided.
        if (!$filename)
            throw new \Exception("No python script name provided!", 1);

        // check if 'Python{version}' command can be executed on system.
        $retval = null;
        system($this->python_command, $retval);
        /**
         * check return value:
         * 126 = command invoked cannot execute.
         * 127 = command not found.
         */
        if (in_array($retval, [126, 127]))
            throw new \Exception("'{$this->python_command}' command does not seem to work on your system. please verify that you have installed Python properly.", 1);

        /**
         * unique filename to store nohup output.
         * python scripts may take some time to execute. the output of a
         * script will be stored in the file, which could be retrieved
         * later using this unique ID.
         */
        $this->nohup_out_id  = time() . rand(100, 999);

        /**
         * generate script filename.
         * note the "/" at the beginning. since the scripts are executed
         * in the shell, the path must be relative to root.
         */
        $script_fn = "/{$this->pyhton_scripts_dir}/$filename.py";

        // check if script file actually exists.
        if (!file_exists($script_fn))
            throw new \Exception("Python script could not be found: $script_fn", 1);

        $params_str = "";
        foreach ($params as $param)
            $params_str .= escapeshellarg($param) . " ";
        
        // build the shell command
        $command = sprintf(
            "PYTHONIOENCODING=%s %s %s %s %s %s",
            $this->encoding,
            ($this->run_in_bg ? "/usr/bin/nohup" : ""),
            $this->python_command,
            $script_fn,
            $params_str,
            (
                $this->run_in_bg ?
                "> {$this->nohup_out_dir}/{$this->nohup_out_id}.{$this->nohup_out_ext} &" :
                ""
            )
        );

        $output = null;
        $retval = null;
        exec($command, $output, $retval);

        return $this->run_in_bg ? $this->nohup_out_id : $output;
    }
}

// EOF
