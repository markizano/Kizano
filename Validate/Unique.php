<?php
/**
 *  Kizano_Validate_Unique
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
 *  @package    Validate
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 *  Verifies a record of that name doesn't already exist in the DB.
 *
 *  @category   Kizano
 *  @package    Validate
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Jack Sleight <jack.sleight@gmail.com>
 *  @author     Markizano Draconus <markizano@markizano.net>
 *  @link       http://framework.zend.com/issues/browse/ZF-1851
 *  @example
 *      <code>
 *
 *      $form = new Zend_Form();
 *      $form->addElement('username', new Zend_Form_Element_Text('username'));
 *      $form->getElement('username')
 *        ->addValidator(
 *          new Kizano_Validate_Unique(
 *              new Model_DbTable_User,
 *              'username',
 *              array('user_id' => 1)
 *          )
 *      );
 *
 *      </code>
 */
class Kizano_Validate_Unique extends Zend_Validate_Abstract
{
    const NOT_UNIQUE = 'uniqueNotUnique';

    protected $_messageTemplates = array(
        self::NOT_UNIQUE => "'%value%' already exists"
    );

    protected $_table;
    protected $_column;

    /**
     *  Constructs a new instance of this validator.
     *
     *  @param Zend_Db_Table_Abstract   $table   The table to perform the check against.
     *  @param String                   $column  The column upon which will be tested for uniqueness.
     *  @param String                   $current The primary key set to ignore when checking for uniqueness.
     *                                              If left empty, will check against ALL rows.
     *
     *  @return void
     */
    public function __construct(Zend_Db_Table_Abstract $table, $column, array $current = array())
    {
        $this->_table = $table;
        $this->_column = $column;
        $this->_current = $current;
    }

    /**
     *  Verifies the input is valid.
     *
     *  @param Mixed    $value   The value to verify
     *  @param Array    $context The form context in which this value was placed.
     *
     *  @return boolean
     */
    public function isValid($value, $context = array())
    {
        $this->_setValue($value);
        $where = array($this->_column . ' = ?' => $value);

        if (isset($this->_current)) {
            $info = $this->_table->info();
            foreach ($this->_table->info('primary') as $column) {
                if (isset($this->_current[$column])) {
                    $where["$column <> ?"] = $this->_current[$column];
                }
            }
        }

        $row = $this->_table->fetchAll($where);
        if (count($row)) {
            $this->_error(self::NOT_UNIQUE);
            return false;
        }
        return true;
    }
}

