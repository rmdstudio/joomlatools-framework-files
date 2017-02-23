<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2011 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/joomlatools-framework-files for the canonical source repository
 */

/**
 * Thumbnails Model State
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Koowa\Component\Files
 */
class ComFilesModelStateThumbnails extends KModelState
{
    protected $_source_container;

    public function set($name, $value = null)
    {
        if ($name == 'source')
        {
            $parts = explode('://', $value);

            $this->_source_container = $parts[0];

            $this->set('name', basename($parts[1]));
            $this->set('folder', dirname($parts[1]));
        }

        return parent::set($name, $value);
    }

    public function remove($name)
    {
        if ($name == 'source') {
            $this->_source_container = null;
        }

        return parent::remove($name);
    }

    public function reset($default = true)
    {
        $this->_source_container = null;

        return parent::reset($default);
    }

    public function getSourceContainer()
    {
        if ($this->_source_container && !$this->_source_container instanceof ComFilesModelEntityContainer)
        {
            $container = $this->getObject('com:files.model.containers')->slug($this->_source_container)->fetch();

            if (!$container->isNew()) {
                $this->_source_container = $container->top();
            }
        }

        return $this->_source_container;
    }
}