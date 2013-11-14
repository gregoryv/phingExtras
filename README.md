phingExtras
===========

Install
-------

Clone from github.

Include the module under phings external modules

    cd /path/to/pear/share/pear/phing/tasks/ext
    ln -s /path/to/phingExtras .

Patch phings FilterChain file. Add the following to phing/types/FilterChain.php

    <?php
    ...

    include_once 'phing/tasks/ext/phingExtras/MustacheProperties.php';

    class FilterChain extends DataType {

        ...

        function addMustacheProperties(MustacheProperties $o) {
            $o->setProject($this->project);
            $this->filterReaders[] = $o;
        }


About
-----

Wrote these during my time at Hypergene AB. They gracefully granted me to
open source them under my own name on github. Thank you.
