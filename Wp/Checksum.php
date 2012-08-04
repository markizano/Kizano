<?php
/**
 *
 *  Kizano_Wp_Checksum
 *  Copyright (C) 2012  Markizano Draconus <markizano@markizano.net>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @TODO: Get rid of the file includes and store them in a PHP array we can use to dynamically adjust
 *          in a single file.
 */
#require_once Kizano_Wp_Checksum_Hashes;

class Kizano_Wp_Checksum
{

    protected $_wp_dir = '';
    protected $_version = 0;
    protected $_lang = '';

    /**
     *
     *
     * @todo Documentation
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    public function setOption($name, $value = null)
    {
        switch ($name) {
            case 'version':
                if ( empty($value) || !is_string($value) ) {
                    throw new RuntimeException("Value for `$name' not a string.");
                }

                $this->_version = $value;
                break;
            case 'wp-dir': case 'wp_dir': case 'wp dir': case 'wpdir':
                if ( empty($value) || !is_string($value) || !file_exists($value) ) {
                    throw new RuntimeException("Cannot stat value for \$options[`$name']: No such file or directory.");
                }

                $this->_wp_dir = realpath($value);
                break;
            case 'lang': case 'locale':
                if ( empty($value) || !is_string($value) ) {
                    throw new RuntimeException('Cannot use non-string value for locale.');
                }

                $this->_lang = $value;
                break;
            default:
                $this->_options[$name] = $value;
        }
    }

    public function getOption($name)
    {
        switch ($name) {
            case 'version':
                return $this->_version;
            case 'wp-dir': case 'wp_dir': case 'wp dir': case 'wpdir':
                return $this->_wp_dir;
            case 'lang': case 'locale':
                return $this->_lang;
            default:
                return $this->_options[$name];
        }
    }

   	public function traverse_directory($dir)
   	{
   	    $result = array();
		if ( $handle = opendir($dir) ) {

			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file != '.' && $file != '..' ) {

					$file = realpath($dir . '/' . $file);
					if ($file === false) continue;

					if ( is_dir($file) ) {
						$result += $this->traverse_directory($file);
					} elseif ( is_file($file) ) {
						$result[] = str_replace($this->_wp_dir . '/', '', $file);
					}
				}
			}

			closedir($handle);
		}

		return $result;
	}

    public function get_file_diff( $file ) {
    	global $wp_version;
    	// core file names have a limited character set
    	$file = preg_replace( '#[^a-zA-Z0-9/_.-]#', '', $file );
    	if ( empty( $file ) || ! is_file( ABSPATH . $file ) )
    		return '<p>Sorry, an error occured. This file might not exist!</p>';
    
    	$key = $wp_version . '-' . $file;
    	$cache = get_option( 'source_files_cache' );
    	if ( ! $cache || ! is_array($cache) || ! isset($cache[$key]) ) {
    		$url = "http://core.svn.wordpress.org/tags/$wp_version/$file";
    		$response = wp_remote_get( $url );
    		if ( is_wp_error( $response ) || 200 != $response['response']['code'] )
    			return '<p>Sorry, an error occured. Please try again later.</p>';
    
    		$clean = $response['body'];
    
    		if ( is_array($cache) ) {
    			if ( count($cache) > 4 ) array_shift( $cache );
    			$cache[$key] = $clean;
    		} else {
    			$cache = array( $key => $clean );
    		}
    		update_option( 'source_files_cache', $cache );
    	} else {
    		$clean = $cache[$key];
    	}
    
    	$modified = file_get_contents( ABSPATH . $file );
    
    	$text_diff = new Text_Diff( explode( "\n", $clean ), explode( "\n", $modified ) );
    	$renderer = new USC_Text_Diff_Renderer();
    	$diff = $renderer->render( $text_diff );
        
    	$r  = "<div class=\"danger-found\">\n";
    	$r .= "\n$diff\n\n";
    	$r .= "</div>";
    	return $r;
    }

    /**
     *
     *
     *
     */
    public function checkAll($version, $lang = null)
    {
        $hashes = $localizations = array();

        $hashfile = dirname(__FILE__) . '/hashes/hashes-'. $wp_version .'.php';
        $localfile = dirname(__FILE__) . '/hashes/hashes-'. $wp_version .'_international.php';

		file_exists($hashfile) && $hashes = include($hashfile);
        file_exists($localfile) && $localizations = include($localfile);

        if (empty($hashes)) {
            trigger_error("Couldn't check against version `$version'.", E_USER_WARNING);
        }

        if (isset($lang, $$lang)) {
            $hashes = array_merge($hashes, $$lang);
        }

        if ((!isset($$lang)) && (isset($lang))) {
            unset(
                $hashes['license.txt'],
                $hashes['wp-config-sample.php'],
                $hashes['readme.html'],
                $hashes['wp-includes/version.php'],
                $hashes['wp-includes/ms-settings.php'],
                $hashes['wp-includes/functions.php'],
                $hashes['wp-includes/ms-load.php'],
                $hashes['wp-includes/wp-db.php'],
                $hashes['wp-includes/default-constants.php'],
                $hashes['wp-includes/load.php'],
                $hashes['wp-load.php'],
                $hashes['wp-admin/setup-config.php']
            );
        }

        $this->_wp_files = $this->traverse_directory($this->_wp_dir);

		foreach($this->_wp_files as $k => $file ) {

			// don't scan unmodified core files
			if ( isset( $hashes[$file] ) ) {
				if ( $hashes[$file] == md5_file("$this->_wp_dir/$file") ) {
					unset($this->wp_files[$k], $hashes[$file]);
					continue;
				} else {
			        $diffs[$file][] = $this->get_file_diff($file);
				}
			}

			// detect old export files
			if ( substr( $file, -9 ) == '.xml_.txt' ) {
		         $old_export[] = $file;
			}
		}

        $this->_diff['diffs'] = $diffs;
        $this->_diff['old_export'] = $old_export;
        $this->_diff['additional'] = $this->_wp_files,
        $this->_diff['missing'] = $hashes;
        
        return empty($diffs) && empty($old_export); # && empty($this->_wp_files) && empty($hashes);
    }
}

