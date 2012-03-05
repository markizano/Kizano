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

require_once 'Text/Diff.php';
require_once 'Text/Diff/Renderer.php';

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
        $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($i as $d) {
            if (preg_match('@\.(git|svn|bzr)|CVS|\.$@', $d->getpathname())) continue;
            $result[] = str_replace("$this->_wp_dir/", '', $d->getPathname());
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
printf("%s($file);", __METHOD__);
    	// core file names have a limited character set
    	$file = preg_replace( '#[^a-zA-Z0-9/_.-]#', '', $file );
    	if ( empty( $file ) || ! is_file( "$this->_wp_dir/$file" ) )
    		return '<p>Sorry, an error occured. This file might not exist!</p>';
    
    	$key = $this->_version . '-' . $file;
		$url = "http://core.svn.wordpress.org/tags/$this->_version/$file";
		$response = file_get_contents($url);
		if (!$response) {
			return '<p>Sorry, an error occured. Please try again later.</p>';
		}

    	$text_diff = new Text_Diff(explode("\n", $response), file("$this->_wp_dir/$file", FILE_IGNORE_NEW_LINE));
    	$renderer = new Text_Diff_Renderer;
    	return $renderer->render($text_diff);
    }

    public function generateHashlist($path)
    {
        $result = array();
        foreach ($this->traverse_directory($path) as $resource) {
            $result[$resource] = sha1_file("$this->_wp_dir/$resource");
        }

        ob_start();
        var_export($result);
        $result = ob_get_clean() . ";\n";
        return $result;
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
		    $result[$file] = $hash = sha1_file("$this->_wp_dir/$file");
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

/*
if ( class_exists( 'Text_Diff_Renderer' ) ) :
class USC_Text_Diff_Renderer extends Text_Diff_Renderer {
	function USC_Text_Diff_Renderer() {
		parent::Text_Diff_Renderer();
	}

	function _startBlock( $header ) {
		return "<span class=\"textdiff-line\">Lines: $header</span>\n";
	}

	function _lines( $lines, $prefix, $class ) {
		$r = '';
		foreach ( $lines as $line ) {
			$line = esc_html( $line );
			$r .= "<div class='{$class}'>{$prefix} {$line}</div>\n";
		}
		return $r;
	}

	function _added( $lines ) {
		return $this->_lines( $lines, '+', 'diff-addedline' );
	}

	function _deleted( $lines ) {
		return $this->_lines( $lines, '-', 'diff-deletedline' );
	}

	function _context( $lines ) {
		return $this->_lines( $lines, '', 'diff-context' );
	}

	function _changed( $orig, $final ) {
		return $this->_deleted( $orig ) . $this->_added( $final );
	}
}
endif;
//*/
