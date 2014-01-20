<?php
/**
 * Koowa Framework - http://developer.joomlatools.com/koowa
 *
 * @copyright	Copyright (C) 2011 - 2013 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/joomlatools/koowa-files for the canonical source repository
 */

/**
 * File Database Row
 *
 * @author  Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Files
 */
class ComFilesDatabaseRowFile extends ComFilesDatabaseRowNode
{
	public static $image_extensions = array('jpg', 'jpeg', 'gif', 'png', 'tiff', 'tif', 'xbm', 'bmp');

	public function __construct(KObjectConfig $config)
	{
		parent::__construct($config);

        $this->addCommandHandler('after.save'  , 'saveThumbnail');
        $this->addCommandHandler('after.delete', 'deleteThumbnail');
	}

	public function save()
	{
		$context = $this->getContext();
		$context->result = false;

		$is_new = $this->isNew();

		if ($this->invokeCommand('before.save', $context) !== false)
		{
			$context->result = $this->_adapter->write(!empty($this->contents) ? $this->contents : $this->file);
			$this->invokeCommand('after.save', $context);
        }

		if ($context->result === false) {
			$this->setStatus(KDatabase::STATUS_FAILED);
		} else {
            $this->setStatus($is_new ? KDatabase::STATUS_CREATED : KDatabase::STATUS_UPDATED);
        }

		return $context->result;
	}

	public function __get($column)
	{
		if (in_array($column, array('size', 'extension', 'modified_date', 'mimetype')))
        {
			$metadata = $this->_adapter->getMetadata();
			return $metadata && array_key_exists($column, $metadata) ? $metadata[$column] : false;
		}

		if ($column == 'filename') {
			return pathinfo($this->name, PATHINFO_FILENAME);
		}

		if ($column == 'metadata')
		{
			$metadata = $this->_adapter->getMetadata();
			if ($this->isImage() && !empty($metadata))
			{
				$image = array(
					'thumbnail' => $this->thumbnail,
					'width' => $this->width,
					'height' => $this->height
				);
				$metadata['image'] = $image;
			}
			return $metadata;
		}

		if (in_array($column, array('width', 'height', 'thumbnail')) && $this->isImage())
        {
			if ($column == 'thumbnail' && isset($this->_data['thumbnail'])) {
				return $this->_data['thumbnail'];
			}
			
			return $this->getImageSize($column);
		}

		return parent::__get($column);
	}	
	
	/**
	 * This method checks for computed properties as well
	 * 
	 * @param string $key
	 */
	public function __isset($key)
	{
		$result = parent::__isset($key);
		
		if (!$result) 
		{
			$var = $this->__get($key);
			if (!empty($var)) {
				$result = true;
			}
		}
		
		return $result;
		
	}

    public function toArray()
    {
        $data = parent::toArray();

        unset($data['file']);
		unset($data['contents']);

		$data['metadata'] = $this->metadata;

		if ($this->isImage()) {
			$data['type'] = 'image';
		}

        return $data;
    }

	public function isImage()
	{
		return in_array(strtolower($this->extension), self::$image_extensions);
	}

	public function getImageSize($column)
	{
		$size = $this->_adapter->getImageSize();

		if ($size === false) {
			return false;
		}

		list($width, $height) = $size;

		switch ($column)
		{
			case 'width':
				return $width;
			case 'height':
				return $height;
			case 'thumbnail':
				if ($width < 200 && $height < 200) {
					// go down to default case
				}
				else {
					$higher = $width > $height ? $width : $height;
					$ratio = 200 / $higher;
					return array_map('round', array('width' => $ratio*$width, 'height' => $ratio*$height));
				}
			default:
				return array('width' => $width, 'height' => $height);
		}
	}

	public function saveThumbnail(KCommandInterface $context = null)
	{
		$result = null;
		$available_extensions = array('jpg', 'jpeg', 'gif', 'png');

		if ($this->isImage() 
			&& $this->getContainer()->getParameters()->thumbnails
			&& in_array(strtolower($this->extension), $available_extensions)
		) {
			$parameters = $this->getContainer()->getParameters();
			$thumbnails_size = isset($parameters['thumbnail_size']) ? $parameters['thumbnail_size'] : array();
			$thumb = $this->getObject('com:files.database.row.thumbnail', array('size' => $thumbnails_size));
			$thumb->source = $this;

			$result = $thumb->save();
		}

		return $result;
	}

	public function deleteThumbnail(KCommandInterface $context = null)
	{
		$thumb = $this->getObject('com:files.model.thumbnails')
            ->container($this->container)
            ->folder($this->folder)
            ->filename($this->name)
			->getItem();

		$result = $thumb->delete();

		return $result;
	}
}
