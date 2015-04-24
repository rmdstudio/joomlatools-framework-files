<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright	Copyright (C) 2007 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		https://github.com/nooku/nooku-framework for the canonical source repository
 */

/**
 * Http Dispatcher Permission
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Files\Library\Dispatcher
 */
class ComFilesDispatcherPermissionHttp extends KDispatcherPermissionAbstract
{
    /**
     * Check if user can can access a component in the administrator backend
     *
     * @return  boolean  Can return both true or false.
     */
    public function canManage()
    {
        return true;
    }
}