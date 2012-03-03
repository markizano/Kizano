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

    protected $_wp_dir = '';        // The root of the directory we will traverse.
    protected $_wp_files = array(); // The files we find in said directory.
    protected $_version = 0;        // The version of WP we test against.
    protected $_lang = '';          // The localization code, if any.
    protected $_diff = array();     // The array of info containing the diffs.
    protected $_quiet = false;      // TRUE to render just file-list. FALSE to list full differences.

    /**
     *
     *
     * @todo Documentation
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * 
     *
     * 
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    /**
     * 
     *
     * 
     */
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
            case 'quiet':
                $this->_quiet = (bool)$value;
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
            case 'diff':
                return $this->_diff;
            case 'quiet':
                return $this->_quiet;
            default:
                return isset($this->_options[$name])? $this->_options[$name]: null;
        }
    }

    /**
     * 
     *
     * 
     */
   	public function traverse_directory($dir)
   	{
   	    $result = array();
		if ( $handle = opendir($dir) ) {
			while ( false !== ( $read = readdir( $handle ) ) ) {
				if ( $read == '.' || $read == '..'|| preg_match('@\.(svn|git|bzr)|CVS@', $read) ) {
				    fprintf(STDERR, "\033[31mSKIPPING\033[0m: $dir/$read\n");
				    continue;
			    }

				$file = realpath("$dir/$read");
				if ( $file === false || empty($file) ) {
				    fprintf(STDERR, "\033[31mCould not realpath $dir/$read\033[0m\n");
				    continue;
			    }

				if ( is_dir($file) ) {
					$result += $this->traverse_directory($file);
				} elseif ( is_file($file) ) {
					$result[] = str_replace($this->_wp_dir . '/', '', $file);
				} else {
				    fprintf(STDERR, "\033[31mUnwaranted\033[0m: $file\n");
				}
			}

			closedir($handle);
		}

		return $result;
	}

    /**
     * 
     *
     * 
     */
    public function get_file_diff( $file )
    {
    	// core file names have a limited character set
    	$file = preg_replace( '#[^a-zA-Z0-9/_.-]#', '', $file );
    	if ( empty( $file ) || ! is_file( $this->_wp_dir . $file ) )
    		return '<p>Sorry, an error occured. This file might not exist!</p>';
    
    	$key = $this->_version . '-' . $file;
		$url = "http://core.svn.wordpress.org/tags/$this->_version/$file";
		$response = file_get_contents($url);
		if (!$response) {
			return '<p>Sorry, an error occured. Please try again later.</p>';
		}

    	$modified = file_get_contents($this->_wp_dir . $file);
    
    	$text_diff = new Text_Diff(explode("\n", $clean), explode("\n", $modified));
    	$renderer = new USC_Text_Diff_Renderer;
    	return $renderer->render($text_diff);
    }

    /**
     *
     *
     *
     */
    public function checkAll()
    {
        $hashes = $localizations = $diffs = $old_export = array();

        $hashfile = dirname(__FILE__) . '/hashes/hashes-'. $this->_version .'.php';
        $localfile = dirname(__FILE__) . '/hashes/hashes-'. $this->_version .'_international.php';

		if (file_exists($hashfile)) {
		    $hashes = include($hashfile);
	    } else {
	        throw new RuntimeException("Cannot stat: `$hashfile' no such file or directory.");
	    }

        file_exists($localfile) && $localizations = include($localfile);

        if (empty($hashes)) {
            throw new RuntimeException("Couldn't check against version `$this->_version'.", E_USER_WARNING);
        }

        if (isset($lang, $$lang)) {
            $hashes = array_merge($hashes, $$lang);
        }

#print Kizano_Misc::var_dump($hashes);
        $this->_wp_files = $this->traverse_directory($this->_wp_dir);
printf("Hashed: %d\nTraversed: %d\n", count($hashes), count($this->_wp_files));

        $result = array();
		foreach( $this->_wp_files as $k => $file ) {
			// don't scan unmodified core files
		    $result[$file] = $hash = md5_file("$this->_wp_dir/$file");
			if ( isset( $hashes[$file] ) ) {
				if ( $hashes[$file] == $hash ) {
					unset($this->wp_files[$k], $hashes[$file]);
					continue;
				} else {
			        $diffs[$file][] = $this->_quiet? "$this->_wp_dir/$file": $this->get_file_diff($file);
				}
			}

			// detect old export files
			if ( substr( $file, -9 ) == '.xml_.txt' ) {
		         $old_export[] = $file;
			}
		}

        sort($diffs);
        sort($old_export);
        sort($this->_wp_files);
        ksort($hashes);

        $this->_diff = array(
            'diffs' => $diffs,
            'old_export' => $old_export,
            'additional' => array_unique(array_values($this->_wp_files)), // Using array_values, we can strip out keys that aren't sequenced.
            'missing' => array_unique(array_keys($hashes)),
            'new' => $result,
        );

        return empty($diffs) && empty($old_export); # && empty($this->_wp_files) && empty($hashes);
    }
}

