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

		foreach( $this->traverse_directory($this->_wp_dir) as $k => $file ) {

			// don't scan unmodified core files
			if ( isset( $hashes[$file] ) ) {
				if ( $hashes[$file] == md5_file( ABSPATH.$file ) ) {
					unset( $this->wp_files[$k] );
					continue;
				} else {
			        $diffs[$file][] = $this->get_file_diff($file);
				}
			}

            //for avoiding false alerts in 25 test
            if ($file == "wp-content/plugins/ultimate-security-checker/securitycheck.class.php" || $file == "wp-content/plugins/ultimate-security-checker/wp-ultimate-security.php") {
                unset( $this->wp_files[$k] );
            }

			// don't scan files larger than 400 KB
			if ( filesize(ABSPATH . $file) > (400 * 1024) ) {
				unset( $this->wp_files[$k] );
			}

			// detect old export files
			if ( substr( $file, -9 ) == '.xml_.txt' ) {
		         $old_export[] = $file;
			}
		}

        if (!isset($diffs) && !isset($old_export)) {
        		return True;
       	} else {
        	    $this->changed_core_files = array(
                'diffs' => $diffs,
                'old_export' => $old_export
                );
        		return False;
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
}

