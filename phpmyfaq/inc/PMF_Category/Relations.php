<?php
/**
 * Relation class for categories and FAQ entries
 *
 * PHP Version 5.2.0
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */

/**
 * PMF_Category_Relations
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */
class PMF_Category_Relations extends PMF_Category_Abstract implements PMF_Category_Interface
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
/**
     * Creates a new entry
     *
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function create(Array $data)
    {
        $query =sprintf("
            INSERT INTO
                %sfaqcategoryrelations
            VALUES
                (%d, '%s', %d, '%s')",
            SQLPREFIX,
            $data['category_id'],
            $data['category_lang'],
            $data['record_id'],
            $data['record_lang']);
            
        $result = $this->db->query($query);
            
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Updates an existing entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function update($id, Array $data)
    {
        
    }
    
    /**
     * Deletes an entry
     *
     * @param integer $id ID
     * 
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function delete($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqcategoryrelations
            WHERE
                category_id = %d",
            SQLPREFIX,
            $id);
            
        if (!is_null($this->language)) {
            $query .= sprintf(" 
            AND 
                category_lang = '%s'",
            $this->language);
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Fetches one entry
     *
     * @param integer $id ID
     * 
     * @return array
     * @throws PMF_Category_Exception
     */
    public function fetch($id)
    {
        $relation = array();
        $query    = sprintf("
            SELECT
                category_id, category_lang, record_id, record_lang
            FROM
                %sfaqcategoryrelations
            WHERE
                category_id = %d",
            SQLPREFIX,
            (int)$id);
        
        if (!is_null($this->language)) {
            $query .= sprintf(" 
            AND 
                category_lang = '%s'
            AND
                record_lang = '%s'",
            $this->language,
            $this->language);
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            if (!is_null($this->language)) {
                $relation = array_shift($this->db->fetchAll($result));
            } else {
                $relation = $this->db->fetchAll($result);
            }
        }
        
        return $relation;
    }
    
    /**
     * Fetches all entries, if parameter = null, otherwise all from the given
     * array like array(1, 2, 3)
     *
     * @param array $ids Array of IDs
     * 
     * @return array
     * @throws PMF_Category_Exception
     */
    public function fetchAll(Array $ids = null)
    {
        $relations = array();
        $query      = sprintf("
            SELECT
                category_id, category_lang, record_id, record_lang
            FROM
                %sfaqcategoryrelations
            WHERE
                1=1",
            SQLPREFIX);
        
        if (!is_null($ids)) {
            $query .= sprintf("
            AND 
                category_id IN (%s)",
            implode(', ', $ids));
        }
        
        if (!is_null($this->language)) {
            $query .= sprintf(" 
            AND 
                category_lang = '%s'
            AND
                record_lang = '%s'",
            $this->language,
            $this->language);
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $relations = $this->db->fetchAll($result);
        }
        
        return $relations;
    }
}