<?php

require_once 'phing/filters/BaseFilterReader.php';
include_once 'phing/filters/ChainableReader.php';
include_once 'Mustache/Autoloader.php';
Mustache_Autoloader::register();

/**
 * Expands Phing Properties, in a mustache template.
 * Dot notation is replaced with camelcase, e.g phing.project.name property
 * will end up in {{phingProjectName}}.
 *
 * NOTE! You need to patch phings FilterChain.php for this to work.
 */
class MustacheProperties extends BaseFilterReader implements ChainableReader {
    protected $logLevel = Project::MSG_VERBOSE;
   
    /**
     * Set level of log messages generated (default = info)
     * @param string $level
     */
    public function setLevel($level)
    {
        switch ($level)
        {
            case "error": $this->logLevel = Project::MSG_ERR; break;
            case "warning": $this->logLevel = Project::MSG_WARN; break;
            case "info": $this->logLevel = Project::MSG_INFO; break;
            case "verbose": $this->logLevel = Project::MSG_VERBOSE; break;
            case "debug": $this->logLevel = Project::MSG_DEBUG; break;
        }
    }
    
    /**
     * Returns the filtered stream. 
     * The original stream is first read in fully, and the Phing properties are expanded.
     * 
     * @return mixed     the filtered stream, or -1 if the end of the resulting stream has been reached.
     * 
     * @exception IOException if the underlying stream throws an IOException
     * during reading
     */
    function read($len = null) {                
        $buffer = $this->in->read($len);
        if($buffer === -1) {
            return -1;
        }
        $mustache = new Mustache_Engine(array('escape' => function($val) {
            return $val;
        }));
        $buffer = $mustache->render($buffer, $this->model);
        return $buffer;
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
        
    public function setModel($model) {
        $this->model = $this->camelcaseKeys($model); 
    }
    
    /**
     * Creates a new ExpandProperties filter using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function chain(Reader $reader) {
        $newFilter = new MustacheProperties($reader);
        $newFilter->setProject($this->getProject());
        $newFilter->setModel($this->getProject()->getProperties());
        return $newFilter;
    }
}


