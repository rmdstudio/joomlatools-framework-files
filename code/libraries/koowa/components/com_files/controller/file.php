<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2011 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa-files for the canonical source repository
 */

/**
 * File Controller
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Files
 */
class ComFilesControllerFile extends ComFilesControllerAbstract
{
	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

		$this->registerCallback('before.add'   , array($this, 'addFile'));
        $this->registerCallback('before.edit'  , array($this, 'addFile'));
	}
	
	protected function _initialize(KObjectConfig $config)
	{
		$config->append(array(
			'behaviors' => array('thumbnailable')
		));

		parent::_initialize($config);
	}

	public function addFile(KControllerContextInterface $context)
	{
		if (empty($context->request->data->file) && $context->request->files->has('file'))
		{
			$context->request->data->file = $context->request->files->file['tmp_name'];
			if (empty($context->request->data->name)) {
				$context->request->data->name = $context->request->files->file['name'];
			}
		}
	}

    protected function _actionRender(KControllerContextInterface $context)
    {
        $model  = $this->getModel();
        $result = null;

        if($model->getState()->isUnique())
        {
            $file = $this->getModel()->getItem();

            try
            {
                $this->getResponse()
                    ->attachTransport('stream')
                    ->setPath($file->fullpath, $file->mimetype);
            }
            catch (InvalidArgumentException $e) {
                throw new KControllerExceptionNotFound('File not found');
            }
        }
        else
        {
            $query     = $this->getRequest()->query;
            $container = $this->getModel()->getContainer();

            // Note: PHP converts dots to underscores in cookie names
            $cookie = json_decode($this->getObject('request')->cookies['com_files_container_'.$container->slug.'_state'], true);

            if (strpos($query->layout, 'compact') === false && is_array($cookie))
            {
                // Check if the folder exists, folder shouldn't exist in query for cookie to be used
                if (isset($cookie['folder']))
                {
                    $adapter = $container->getAdapter('folder');
                    $adapter->setPath($container->path . '/' . $cookie['folder']);
                    // Unset folder cookie if path does not exists.
                    if (!$adapter->exists()) {
                        unset($cookie['folder']);
                    }
                }

                foreach ($cookie as $key => $value)
                {
                    if (!$query->has($key)) {
                        $query->$key = $value;
                    }
                }

                $model->getState()->setValues($query->toArray());
            }

            $result = parent::_actionRender($context);
        }

        return $result;
    }
}
