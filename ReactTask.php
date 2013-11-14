<?php
/**
    Reacts to changes in filesets.

    Example:

    <target name="mytarget">
      <react refresh="1.7" cmd="dosomething.sh">
        <fileset dir=".">
            <include name="*.txt" />
        </fileset>
      </react>
    </target>

    Will call dosomething.sh script when a txt file has changed in the current
    directory every 1.7 seconds.

    $Id: ReactTask.php 126 2013-07-23 10:05:12Z gregory.vincic $
*/
require_once "phing/Task.php";

class ReactTask extends Task {

    /** Command to execute */
    private $cmd = null;

    public function setCmd($str) {
      $this->cmd = $str;
    }

    /** Refresh time in microseconds, defaults to 1 second. */
    private $refresh = 1000000;

    public function setRefresh($str) {
        if($str != null && is_numeric($str)) {
            $this->refresh = $str*1000000;
        }
    }

    /** Any filesets of files that should be appended. */
    private $filesets = array();

    function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    /** Uses phps passthru to execute the configured command every X seconds */
    public function main() {
        $lastmtime = null;
        $this->log("Refreshing every " . $this->refresh/1000000 . " seconds.\n", Project::MSG_INFO);
        while(1) {
            $mtimes = $this->rlist();
            if(count($mtimes) > 0 && max($mtimes) > $lastmtime) {
                passthru($this->cmd);
                $lastmtime = max($mtimes); 
                $this->log(date(DATE_RFC822) . " waiting...", Project::MSG_INFO);
            }
            usleep($this->refresh);
        }
    }

    /** Lists modification times of all the files defined by your filesets. */
    private function rlist() {
        $res = array();
        foreach($this->filesets as $fs) {
            try {
                $files = $fs->getDirectoryScanner($this->project)->getIncludedFiles();
                foreach ($files as $file) {
                    $path = $fs->dir . "/" . $file;
                    $res[] = filemtime($path);
                }
            } catch (BuildException $be) {
                $this->log($be->getMessage(), Project::MSG_WARN);
            }
        }
        return $res;
    }
}

?>
