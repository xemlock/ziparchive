<?php

require_once dirname(__FILE__) . '/pclzip/pclzip.lib.php';

class ziparchive_PclZip extends PclZip
{
    protected $_contents;

    /**
     * This function is basically a rewrite of privList() but only extracting
     * info about a single entry.
     *
     * @param  int $p_option    PCLZIP_OPT_BY_INDEX or PCLZIP_OPT_BY_NAME
     * @param  int|string $p_option_value index or name depending on option set
     * @return array|int        0 on failure, an array with the entry properties
     */
    public function getInfo($p_option, $p_option_value)
    {
        $this->privDisableMagicQuotes();

        if (($this->zip_fd = @fopen($this->zipname, 'rb')) == 0) {
            $this->privSwapBackMagicQuotes();
            PclZip::privErrorLog(PCLZIP_ERR_READ_OPEN_FAIL, 'Unable to open archive \'' . $this->zipname . '\' in binary read mode');
            return 0;
        }

        $v_central_dir = array();
        if (($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1) {
            $this->privSwapBackMagicQuotes();
            return 0;
        }

        @rewind($this->zip_fd);
        if (@fseek($this->zip_fd, $v_central_dir['offset'])) {
            $this->privSwapBackMagicQuotes();
            PclZip::privErrorLog(PCLZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');
            return 0;
        }

        $p_info = null;

        switch ($p_option) {
            case PCLZIP_OPT_BY_INDEX:
                $index = (int) $p_option_value;
                for ($i = 0; $i < $v_central_dir['entries']; ++$i) {
                    if (($v_result = $this->privReadCentralFileHeader($v_header)) != 1) {
                        $this->privSwapBackMagicQuotes();
                        return $v_result;
                    }
                    if ($i === $index) {
                        $v_header['index'] = $i;
                        $this->privConvertHeader2FileInfo($v_header, $p_info);
                        break;
                    }
                }
                break;

            case PCLZIP_OPT_BY_NAME:
                $name = (string) $p_option_value;
                break;
        }

        $this->privCloseFd();
        $this->privSwapBackMagicQuotes();

        return $p_info;
    }

    /**
     * @param  string $name
     * @return array|int
     */
    public function addEmptyFolder($name)
    {
        return $this->privAdd(array(
            array(
                'filename' => (string) $name,
                'type'     => 'virtual_folder',
            ),
        ), $p_result_list, $p_options);
    }

    /**
     * @param  string $p_filename
     * @param  array &$p_header
     * @return int
     */
    protected function privAddVirtualFolder($p_filename, &$p_header = null)
    {
        $p_filename = trim($p_filename, '/\\') . '/';

        $p_header['version'] = 20;
        $p_header['version_extracted'] = 10;
        $p_header['flag'] = 0;
        $p_header['compression'] = 0;
        $p_header['crc'] = 0;
        $p_header['size'] = 0;
        $p_header['compressed_size'] = 0;
        $p_header['disk'] = 0;
        $p_header['offset'] = 0;
        $p_header['internal'] = 0;
        $p_header['external'] = 0x00000010;
        $p_header['status'] = 'ok';
        $p_header['index'] = -1;
        $p_header['mtime'] = time();
        $p_header['filename'] = $p_filename;
        $p_header['filename_len'] = strlen($p_filename);
        $p_header['stored_filename'] = $p_filename;
        $p_header['comment'] = '';
        $p_header['comment_len'] = 0;
        $p_header['extra'] = '';
        $p_header['extra_len'] = 0;

        return $this->privWriteFileHeader($p_header);
    }

    /**
     * @param  array $p_filedescr_list
     * @param  array $p_result_list
     * @param  array $p_options
     * @return array
     */
    function privAddFileList($p_filedescr_list, &$p_result_list, &$p_options)
    {
        // add virtual folders first, as they are not supported by the
        // parent implementation

        $v_result = 1;
        $v_nb = count($p_result_list);

        for ($i = 0; ($i < count($p_filedescr_list)) && ($v_result == 1); ++$i) {
            $p_filedescr = &$p_filedescr_list[$i];

            // ensure file names have no trailing slashes - as might be the
            // case when the original file name is completely removed by
            // PCLZIP_OPT_ADD_PATH and PCLZIP_OPT_REMOVE_PATH options in add()
            if (strpos($p_filedescr['type'], 'file') !== false &&
                substr($p_filedescr['stored_filename'], -1) === '/'
            ) {
                $p_filedescr['stored_filename'] = substr($p_filedescr['stored_filename'], 0, -1);
            }

            if ($p_filedescr['type'] === 'virtual_folder') {
                $v_result = $this->privAddVirtualFolder($p_filedescr['filename'], $v_header);
                if ($v_result != 1) {
                    return $v_result;
                }
                $p_result_list[$v_nb++] = $v_header;
                unset($p_filedescr_list[$i]);
            }
        }

        $p_filedescr_list = array_values($p_filedescr_list);
        $v_result = parent::privAddFileList($p_filedescr_list, $p_result_list, $p_options);

        return $v_result;
    }

    /**
     * @param  array $p_header
     * @param  array &$p_info
     * @return int
     */
    function privConvertHeader2FileInfo($p_header, &$p_info)
    {
        $v_result = PclZip::privConvertHeader2FileInfo($p_header, $p_info);
        if ($v_result === 1) {
            $p_info['compression'] = $p_header['compression'];
        }
        return $v_result;
    }
}
