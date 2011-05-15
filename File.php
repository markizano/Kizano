<?php
/**
 *  Kizano_File
 *
 *  LICENSE
 *
 *  This source file is subject to the new BSD license that is bundled
 *  with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://framework.zend.com/license/new-bsd
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@zend.com so we can send you a copy immediately.
 *
 *  @category   Kizano
 *  @package    File
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 * Static class placeholder for the file types we accept and abstraction to any
 * base functionality.
 *
 *  @category   Kizano
 *  @package    File
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_File
{
    # List of ALL accepted file types and their associated extensions.
    public static $types = array(
        'audio'                => array(
            'audio/mpeg'                                                                => 'mp3',
            'audio/x-wav'                                                               => 'wav',
            'audio/ogg'                                                                 => 'ogg',
        ),
        'image'                => array(
            'image/jpg'                                                                 => 'jpg',
            'image/jpeg'                                                                => 'jpg',
            'image/pjpeg'																=> 'jpg',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'image/gif'                                                                 => 'gif',
        ),
        'video'                => array(
            'video/mpeg'                                                                => 'mpg',
            'video/mp4'                                                                 => 'mp4',
            'video/x-flv'                                                               => 'flv',
            'video/x-msvideo'                                                           => 'avi',
            'video/ogg'                                                                 => 'ogg',
        ),
        'document'             => array(
            'text/plain'                                                                => 'txt',
            'application/pdf'                                                           => 'pdf',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/vnd.ms-excel'                                                  => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.oasis.opendocument.spreadsheet'                            => 'ods',
            'application/vnd.oasis.opendocument.text'                                   => 'odt',
        )
    );
}

