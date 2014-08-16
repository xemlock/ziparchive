<?php

require_once dirname(__FILE__) . '/PclZip.php';

class ziparchive_ZipArchive
{
    const OVERWRITE = 1;

    const CREATE = 2;

    const EXCL = 3;

    const CHECKCONS = 4;


    const ER_EXISTS = 1;

    const ER_INCONS = 2;

    const ER_INVAL = 3;

    const ER_MEMORY = 4;

    const ER_NOENT = 5;

    const ER_NOZIP = 6;

    const ER_OPEN = 7;

    const ER_READ = 8;

    const ER_SEEK = 9;

    public $status;
    public $statusSys;
    public $numFiles;
    public $filename;
    public $comment;

    protected $_pclzip;

    protected $_contents;

    function open($filename, $flags = 0)
    {
        if ($this->_pclzip) {
        }
        $this->_pclzip = new ziparchive_PclZip($filename);
        $this->_contents = null;
        $this->_refreshProperties();
    }

    function close()
    {}

    public function addEmptyDir($dirname)
    {
        $result = $this->_pclzip->addEmptyFolder((string) $dirname);
        $this->_refreshProperties();
        return $result !== 0;
    }

    /**
     * @param  string $filename
     * @param  string $localname OPTIONAL
     * @param  int $start OPTIONAL
     * @param  int $length OPTIONAL
     * @return bool
     */
    function addFile($filename, $localname = null, $start = 0, $length = 0)
    {
        if ($localname !== null) {
            $p_add_dir = $localname;
            $p_remove_dir = $filename;
        } else {
            $p_add_dir = '';
            $p_remove_dir = '';
        }
        $result = $this->_pclzip->add($filename, PCLZIP_OPT_ADD_PATH, $p_add_dir, PCLZIP_OPT_REMOVE_PATH, $p_remove_dir);
        var_dump($result);
        // TODO start, length
        if ($result !== 0) {
            $this->_contents = null;
            return true;
        }
        return false;
    }

    /**
     * @param  string $localname
     * @param  string $contents
     * @return bool
     */
    function addFromString($localname, $contents)
    {
        $result = $this->_pclzip->add(array(
            array(
                PCLZIP_ATT_FILE_NAME => $localname,
                PCLZIP_ATT_FILE_CONTENT => $contents,
            ),
        ));
        if ($result !== 0) {
            $this->_contents = null;
            return true;
        }
        return false;
    }

    /**
     * @param  int $index
     * @return bool
     */
    public function deleteIndex($index)
    {
        $result = $this->_pclzip->delete(PCLZIP_OPT_BY_INDEX, (int) $index);
        if ($result !== 0) {
            $this->_refreshProperties();
            $this->_contents = null;
            return true;
        }
        return false;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function deleteName($name)
    {
        $result = $this->_pclzip->delete(PCLZIP_OPT_BY_NAME, $name);
        if ($result !== 0) {
            $this->_refreshProperties();
            $this->_contents = null;
            return true;
        }
        return false;
    }

    /**
     * @param  int $index
     * @param  int $flags OPTIONAL
     * @return array|false
     */
    public function statIndex($index, $flags = 0)
    {
        $contents = $this->_getContents();
        if (isset($contents[$index])) {
            return $this->_statFromInfo($contents[$index]);
        }
        return false;
    }

    /**
     * @param  string $name
     * @param  int $flags OPTIONAL
     * @return array|false
     */
    public function statName($name, $flags = 0)
    {
        return false;        
    }

    /**
     * @param  array $info
     * @return array
     */
    protected function _statFromInfo(array $info)
    {
        return array(
            'name'  => $info['filename'],
            'index' => $info['index'],
            'crc'   => $info['crc'],
            'size'  => $info['size'],
            'mtime' => $info['mtime'],
            'comp_size' => $info['compressed_size'],
            'comp_method' => $info['compression'],
        );
    }

    protected function _refreshProperties()
    {
        $properties = $this->_pclzip->properties();

        if ($properties) {
            $this->numFiles = $properties['nb'];
            $this->comment = $properties['comment'];
            $this->status = $properties['status'];
        } else {
            $this->numFiles = 0;
            $this->comment = null;
            $this->status = null;
        }
    }

    protected function _getContents()
    {
        if ($this->_contents === null) {
            $this->_contents = $this->_pclzip->listContent();
        }
        return $this->_contents;
    }
}
