<?php

require_once dirname(__FILE__) . '/pclzip/pclzip.lib.php';

/**
 * This is an enhanced version of PclZip class with the following additions:
 * - support for adding empty folders
 * - support for adding files with completely changed local name, not only path
 * - compression algorithm info is added to file info
 *
 * @package ziparchive
 * @author  xemlock
 */
class ziparchive_PclZip extends PclZip
{
    /**
     * @param  string $name
     * @return array|int
     */
    function addEmptyFolder($name)
    {
        return $this->privAdd(array(
            array(
                'filename' => (string) $name,
                'type' => 'virtual_folder',
            ),
        ), $p_result_list, $p_options);
    }

    /**
     * @param  string $p_filename
     * @param  array &$p_header
     * @return int
     */
    function privAddVirtualFolder($p_filename, &$p_header)
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
