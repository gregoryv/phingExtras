<?php

require_once 'phing/Task.php';
include_once 'Michelf/Markdown.php';
include_once 'Michelf/MarkdownExtra.php';
use \Michelf\MarkdownExtra;
require_once 'MustacheProperties.php';


class MarkdownTask extends Task {

    /** Directory where the html files will be written to. */
    private $dest = null;
    public function setDest($value) {
        $this->dest = $value;
    }

    /** HTML mustache template to use for conversion markdown files. */
    private $template = "<html><body>{{body}}</body></html>";
    public function setTemplate($path) {
        if($path != null) {
            $this->template = file_get_contents($path);
        }
    }

    /** Any filters to be applied before append happens. */
    private $filterChains = array();
    /**
     * Creates a filterchain
     *
     * @return FilterChain The created filterchain object
     */
    function createFilterChain() {
        $num = array_push($this->filterChains, new FilterChain($this->project));
        return $this->filterChains[$num-1];
    }

    /** Any filesets of files that should be appended. */
    private $filesets = array();
    function createFileSet() {
        $num = array_push($this->filesets, new FileSet());
        return $this->filesets[$num-1];
    }

    /** Converts array keys e.g. [a.bra.kadabra] to [aBraKadabra]. */
    private function camelcaseKeys($arr) {
        $res = array();
        foreach ($arr as $key => $value) {
            $parts = explode('.', $key);
            $nkey = $parts[0];
            for ($i=1; $i < count($parts); $i++) {
                $nkey .= ucfirst($parts[$i]);
            }
            $res[$nkey] = $value;
        }
        return $res;
    }

    function main() {
        foreach($this->filesets as $fs) {
            try {
                $files = $fs->getDirectoryScanner($this->project)->getIncludedFiles();
                foreach ($files as $file) {
                    $path = $fs->dir . "/" . $file;
                    $finfo =  pathinfo($file);
                    // Create missing subdirectories
                    $destPath = $this->dest . "/" . $finfo['dirname'];
                    if(!is_dir($destPath)) {
                        mkdir($destPath);
                    }
                    $dest = $destPath."/".$finfo['filename'].".html";
                    $doConversion = !is_file($dest) || filemtime($path) > filemtime($dest);
                    if(doConversion) {
                        $this->convertFile($path, $dest);
                    }
                }
            } catch (BuildException $be) {
                $this->log($be->getMessage(), Project::MSG_WARN);
            }
        }
    }

    /**
     * Converts path using the class template into html and saves it to dest
     */
    private function convertFile($path, $dest) {
        $in = FileUtils::getChainedReader(new FileReader($path), $this->filterChains, $this->project);
        $rawMarkdown = "";
        while (-1 !== ($buffer = $in->read())) {
            $rawMarkdown .= $buffer;
        }
        $in->close();
        $html = MarkdownExtra::defaultTransform($rawMarkdown);
        //$this->model['body'] = $html; //$this->mustache->render($html, $this->model);
        $reader = null;
        $reader = new MustacheProperties(new StringReader($this->template));
        $reader->setProject($this->getProject());
        $p = $this->getProject()->getProperties();
        $p['body'] = $html;
        $reader->setModel($p);
        $complete = "";
        $buffer = $reader->read();
        $reader->close();
        file_put_contents($dest, $buffer);
        //$this->log("Writing: " . $dest, Project::MSG_INFO);
    }
}

