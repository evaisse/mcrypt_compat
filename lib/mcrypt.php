<?php
/**
 * mcrypt polyfill
 *
 * PHP 7.1 removed the mcrypt extension. This provides a compatibility layer for legacy applications.
 *
 * PHP versions 5 and 7
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

require __DIR__ . '/../vendor/autoload.php';

use phpseclib\Crypt\Rijndael;
use phpseclib\Crypt\Twofish;
use phpseclib\Crypt\Blowfish;
use phpseclib\Crypt\TripleDES;
use phpseclib\Crypt\DES;
use phpseclib\Crypt\RC2;
use phpseclib\Crypt\RC4;
use phpseclib\Crypt\Random;
use phpseclib\Crypt\Base;

if (!defined('MCRYPT_MODE_ECB')) {
    /**#@+
     * mcrypt constants
     *
     * @access public
     */
    // http://php.net/manual/en/mcrypt.constants.php
    define('MCRYPT_MODE_ECB', 'ecb');
    define('MCRYPT_MODE_CBC', 'cbc');
    define('MCRYPT_MODE_CFB', 'cfb');
    define('MCRYPT_MODE_OFB', 'ofb');
    define('MCRYPT_MODE_NOFB', 'nofb');
    define('MCRYPT_MODE_STREAM', 'stream');

    define('MCRYPT_ENCRYPT', 0);
    define('MCRYPT_DECRYPT', 1);
    define('MCRYPT_DEV_RANDOM', 0);
    define('MCRYPT_DEV_URANDOM', 1);
    define('MCRYPT_RAND', 2);    

    // http://php.net/manual/en/mcrypt.ciphers.php
    define('MCRYPT_3DES', 'tripledes');
    define('MCRYPT_ARCFOUR_IV', 'arcfour-iv');
    define('MCRYPT_ARCFOUR', 'arcfour');
    define('MCRYPT_BLOWFISH', 'blowfish');
    define('MCRYPT_CAST_128', 'cast-128');
    define('MCRYPT_CAST_256', 'cast-256');
    define('MCRYPT_CRYPT', 'crypt');
    define('MCRYPT_DES', 'des');
    // MCRYPT_DES_COMPAT?
    // MCRYPT_ENIGMA?
    define('MCRYPT_GOST', 'gost');
    define('MCRYPT_IDEA', 'idea');
    define('MCRYPT_LOKI97', 'loki97');
    define('MCRYPT_MARS', 'mars');
    define('MCRYPT_PANAMA', 'panama');
    define('MCRYPT_RIJNDAEL_128', 'rijndael-128');
    define('MCRYPT_RIJNDAEL_192', 'rijndael-192');
    define('MCRYPT_RIJNDAEL_256', 'rijndael-256');
    define('MCRYPT_RC2', 'rc2');
    // MCRYPT_RC4?
    define('MCRYPT_RC6', 'rc6');
    // MCRYPT_RC6_128
    // MCRYPT_RC6_192
    // MCRYPT_RC6_256
    define('MCRYPT_SAFER64', 'safer-sk64');
    define('MCRYPT_SAFER128', 'safer-sk128');
    define('MCRYPT_SAFERPLUS', 'saferplus');
    define('MCRYPT_SERPENT', 'serpent');
    // MCRYPT_SERPENT_128?
    // MCRYPT_SERPENT_192?
    // MCRYPT_SERPENT_256?
    define('MCRYPT_SKIPJACK', 'skipjack');
    // MCRYPT_TEAN?
    define('MCRYPT_THREEWAY', 'threeway');
    define('MCRYPT_TRIPLEDES', 'tripledes');
    define('MCRYPT_TWOFISH', 'twofish');
    // MCRYPT_TWOFISH128?
    // MCRYPT_TWOFISH192?
    // MCRYPT_TWOFISH256?
    define('MCRYPT_WAKE', 'wake');
    define('MCRYPT_XTEA', 'xtea');
    /**#@-*/
}

if (!function_exists('phpseclib_mcrypt_list_algorithms')) {
    /**
     * Gets an array of all supported ciphers.
     *
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_list_algorithms($lib_dir = '')
    {
        return array(
            'rijndael-128',
            'twofish',
            'rijndael-192',
            'blowfish-compat',
            'des',
            'rijndael-256',
            'blowfish',
            'rc2',
            'tripledes',
            'arcfour'
        );
    }

    /**
     * Gets an array of all supported modes
     *
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_list_modes($lib_dir = '')
    {
        return array(
            'cbc',
            //'cfb',
            'ctr',
            'ecb',
            'ncfb',
            'nofb',
            //'ofb',
            'stream'
        );
    }

    /**
     * Creates an initialization vector (IV) from a random source
     *
     * The IV is only meant to give an alternative seed to the encryption routines. This IV does not need
     * to be secret at all, though it can be desirable. You even can send it along with your ciphertext
     * without losing security.
     *
     * @param int $size
     * @param int $source optional
     * @return string
     * @access public
     */
    function phpseclib_mcrypt_create_iv($size, $source = MCRYPT_DEV_URANDOM)
    {
        if ($size < 1 || $size > 0x7FFFFFFF) {
            trigger_error('mcrypt_create_iv(): Cannot create an IV with a size of less than 1 or greater than 2147483647', E_USER_WARNING);
            return '';
        }
        return Random::string($size);
    }

    /**
     * Opens the module of the algorithm and the mode to be used
     *
     * This function opens the module of the algorithm and the mode to be used. The name of the algorithm
     * is specified in algorithm, e.g. "twofish" or is one of the MCRYPT_ciphername constants. The module
     * is closed by calling mcrypt_module_close().
     *
     * @param string $algorithm
     * @param string $algorithm_directory
     * @param string $mode
     * @param string $mode_directory
     * @return object
     * @access public
     */
    function phpseclib_mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory)
    {
        $modeMap = array(
            'ctr' => Base::MODE_CTR,
            'ecb' => Base::MODE_ECB,
            'cbc' => Base::MODE_CBC,
            'ncfb'=> Base::MODE_CFB,
            'nofb'=> Base::MODE_OFB,
            'stream' => Base::MODE_STREAM
        );
        switch (true) {
            case !isset($modeMap[$mode]):
            case $mode == 'stream' && $algorithm != 'arcfour':
            case $algorithm == 'arcfour' && $mode != 'stream':
                trigger_error('mcrypt_module_open(): Could not open encryption module', E_USER_WARNING);
                return false;
        }
        switch ($algorithm) {
            case 'rijndael-128':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(128);
                break;
            case 'twofish':
                $cipher = new Twofish($modeMap[$mode]);
                break;
            case 'rijndael-192':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(192);
                break;
            case 'blowfish-compat':
                $cipher = new Blowfish($modeMap[$mode]);
                break;
            case 'des':
                $cipher = new DES($modeMap[$mode]);
                break;
            case 'rijndael-256':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(256);
                break;
            case 'blowfish':
                $cipher = new Blowfish($modeMap[$mode]);
                break;
            case 'rc2':
                $cipher = new RC2($modeMap[$mode]);
                break;
            case'tripledes':
                $cipher = new TripleDES($modeMap[$mode]);
                break;
            case 'arcfour':
                $cipher = new RC4();
                break;
            default:
                trigger_error('mcrypt_module_open(): Could not open encryption module', E_USER_WARNING);
                return false;
        }

        $cipher->disablePadding();

        return $cipher;
    }

    /**
     * Returns the maximum supported keysize of the opened mode
     *
     * Gets the maximum supported key size of the algorithm in bytes.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_key_size(Base $td)
    {
        // invalid parameters with mcrypt result in warning's. type hinting, as this function is doing,
        // produces a catchable fatal error.

        $backup = clone $td;
        $backup->setKeyLength(9999);
        return $backup->getKeyLength();
    }

    /**
     * Returns the maximum supported keysize of the opened mode
     *
     * Gets the maximum supported keysize of the opened mode.
     *
     * @param string $algorithm
     * @param string $lib_dir
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_module_get_algo_key_size($algorithm, $lib_dir = '')
    {
        $mode = $algorithm == 'rc4' ? 'stream' : 'cbc';
        $td = @phpseclib_mcrypt_module_open($algorithm, '', $mode, '');
        return phpseclib_mcrypt_enc_get_key_size($td);
    }

    /**
     * Returns the size of the IV of the opened algorithm
     *
     * This function returns the size of the IV of the algorithm specified by the encryption
     * descriptor in bytes. An IV is used in cbc, cfb and ofb modes, and in some algorithms
     * in stream mode.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_iv_size(Base $td)
    {
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the blocksize of the opened algorithm
     *
     * Gets the blocksize of the opened algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_block_size(Base $td)
    {
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the blocksize of the specified algorithm
     *
     * Gets the blocksize of the specified algorithm.
     *
     * @param string $algorithm
     * @param string $lib_dir
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_module_get_algo_block_size($algorithm, $lib_dir = '')
    {
        // cbc isn't a valid mode for rc4 but that's ok: -1 will still be returned
        $td = @phpseclib_mcrypt_module_open($algorithm, '', 'cbc', '');
        if ($td === false) {
            return -1;
        }
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the name of the opened algorithm
     *
     * This function returns the name of the algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_enc_get_algorithms_name(Base $td)
    {
        $reflection = new \ReflectionObject($td);
        switch ($reflection->getShortName()) {
            case 'Rijndael':
                return 'RIJNDAEL-' . $td->getBlockLength();
            case 'Twofish':
                return 'TWOFISH';
            case 'Blowfish':
                return 'BLOWFISH'; // what about BLOWFISH-COMPAT?
            case 'DES':
                return 'DES';
            case 'RC2':
                return 'RC2';
            case 'TripleDES':
                return 'TRIPLEDES';
            case 'RC4':
                return 'ARCFOUR';
        }

        return false;
    }

    /**
     * Returns the name of the opened mode
     *
     * This function returns the name of the mode.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_enc_get_modes_name(Base $td)
    {
        $modeMap = array(
            Base::MODE_CTR => 'CTR',
            Base::MODE_ECB => 'ECB',
            Base::MODE_CBC => 'CBC',
            Base::MODE_CFB => 'nCFB',
            Base::MODE_OFB => 'nOFB',
            Base::MODE_STREAM => 'STREAM'
        );

        return isset($modeMap[$td->mode]) ? $modeMap[$td->mode] : false;
    }

    /**
     * Checks whether the encryption of the opened mode works on blocks
     *
     * Tells whether the algorithm of the opened mode works on blocks (e.g. FALSE for stream, and TRUE for cbc, cfb, ofb)..
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_algorithm_mode(Base $td)
    {
        return $td->mode != Base::MODE_STREAM;
    }

    /**
     * Checks whether the algorithm of the opened mode is a block algorithm
     *
     * Tells whether the algorithm of the opened mode is a block algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_algorithm(Base $td)
    {
        return phpseclib_mcrypt_enc_get_algorithms_name($td) != 'ARCFOUR';
    }

    /**
     * Checks whether the opened mode outputs blocks
     *
     * Tells whether the opened mode outputs blocks (e.g. TRUE for cbc and ecb, and FALSE for cfb and stream).
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_mode(Base $td)
    {
        return $td->mode == Base::MODE_ECB || $td->mode == Base::MODE_CBC;
    }

    /**
     * Runs a self test on the opened module
     *
     * This function runs the self test on the algorithm specified by the descriptor td.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_self_test(Base $td)
    {
        return true;
    }

    /**
     * Returns the maximum supported keysize of the opened mode
     *
     * Gets the maximum supported key size of the algorithm in bytes.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $key
     * @param string $iv
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_generic_init(Base $td, $key, $iv)
    {
        $iv_size = phpseclib_mcrypt_enc_get_iv_size($td);
        if (strlen($iv) != $iv_size) {
            trigger_error('mcrypt_generic_init(): Iv size incorrect; supplied length: ' . strlen($iv) . ', needed: ' . $iv_size, E_USER_WARNING);
        }
        if (!strlen($key)) {
            trigger_error('mcrypt_generic_init(): Key size is 0', E_USER_WARNING);
            return -3;
        }
        $max_key_size = phpseclib_mcrypt_enc_get_key_size($td) >> 3;
        if (strlen($key) > $max_key_size) {
            trigger_error('mcrypt_generic_init(): Key size too large; supplied length: ' . strlen($key) . ', max: ' . $max_key_size, E_USER_WARNING);
        }
        // both mcrypt and phpseclib 1.0/2.0 null pad keys that are of an invalid length to the next appropriate size
        $td->setKey($key);
        // the IV is similarily null padded
        $td->setIV($iv);

        $td->enableContinuousBuffer();
        $td->mcrypt_polyfill_init = true;

        return 0;
    }

    /**
     * Encrypt / decrypt data
     *
     * Performs checks common to both mcrypt_generic and mdecrypt_generic
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @param string $op
     * @return string|bool
     * @access private
     */
    function phpseclib_mcrypt_generic_helper(Base $td, &$data, $op)
    {
        // in the orig mcrypt, if mcrypt_generic_init() was called and an empty key was provided you'd get the following error:
        // Warning: mcrypt_generic(): supplied resource is not a valid MCrypt resource
        // that error doesn't really make a lot of sense in this context since $td is not a resource nor should it be one.
        // in light of that we'll just display the same error that you get when you don't call mcrypt_generic_init() at all
        if (!isset($td->mcrypt_polyfill_init)) {
            trigger_error('m' . $op . '_generic(): Operation disallowed prior to mcrypt_generic_init().', E_USER_WARNING);
            return false;
        }

        // phpseclib does not currently provide a way to retrieve the mode once it has been set via "public" methods
        if ($td->mode == Base::MODE_CBC || $td->mode == Base::MODE_ECB) {
            $block_length = phpseclib_mcrypt_enc_get_iv_size($td);
            $extra = strlen($data) % $block_length;
            if ($extra) {
                $data.= str_repeat("\0", $block_length - $extra);
            }
        }

        return $op == 'crypt' ? $td->encrypt($data) : $td->decrypt($data);
    }

    /**
     * This function encrypts data
     *
     * This function encrypts data. The data is padded with "\0" to make sure the length of the data
     * is n * blocksize. This function returns the encrypted data. Note that the length of the
     * returned string can in fact be longer than the input, due to the padding of the data.
     *
     * If you want to store the encrypted data in a database make sure to store the entire string as
     * returned by mcrypt_generic, or the string will not entirely decrypt properly. If your original
     * string is 10 characters long and the block size is 8 (use mcrypt_enc_get_block_size() to
     * determine the blocksize), you would need at least 16 characters in your database field. Note
     * the string returned by mdecrypt_generic() will be 16 characters as well...use rtrim($str, "\0")
     * to remove the padding.
     *
     * If you are for example storing the data in a MySQL database remember that varchar fields
     * automatically have trailing spaces removed during insertion. As encrypted data can end in a
     * space (ASCII 32), the data will be damaged by this removal. Store data in a tinyblob/tinytext
     * (or larger) field instead.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic_helper($td, $data, 'crypt');
    }

    /**
     * Decrypts data
     *
     * This function decrypts data. Note that the length of the returned string can in fact be
     * longer than the unencrypted string, due to the padding of the data.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @return string|bool
     * @access public
     */
    function phpseclib_mdecrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic_helper($td, $data, 'decrypt');
    }

    /**
     * This function deinitializes an encryption module
     *
     * This function terminates encryption specified by the encryption descriptor (td).
     * It clears all buffers, but does not close the module. You need to call
     * mcrypt_module_close() yourself. (But PHP does this for you at the end of the 
     * script.)
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_generic_deinit(Base $td)
    {
        if (!isset($td->mcrypt_polyfill_init)) {
            trigger_error('mcrypt_generic_deinit(): Could not terminate encryption specifier', E_USER_WARNING);
            return false;
        }

        $td->disableContinuousBuffer();
        unset($td->mcrypt_polyfill_init);
        return true;
    }

    /**
     * Closes the mcrypt module
     *
     * Closes the specified encryption handle.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_close(Base $td)
    {
        //unset($td->mcrypt_polyfill_init);
        return true;
    }

    /**
     * Returns an array with the supported keysizes of the opened algorithm
     *
     * Returns an array with the key sizes supported by the specified algorithm.
     * If it returns an empty array then all key sizes between 1 and mcrypt_module_get_algo_key_size()
     * are supported by the algorithm.
     *
     * @param string $algorithm
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir = '')
    {
        switch ($algorithm) {
            case 'rijndael-128':
            case 'rijndael-192':
            case 'rijndael-256':
            case 'twofish':
                return array(16, 24, 32);
            case 'des':
                return array(8);
            case 'tripledes':
                return array(24);
            //case 'arcfour':
            //case 'blowfish':
            //case 'blowfish-compat':
            //case 'rc2':
            default:
                return array();
        }
    }

    /**
     * Returns an array with the supported keysizes of the opened algorithm
     *
     * Gets the supported key sizes of the opened algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_enc_get_supported_key_sizes(Base $td)
    {
        $algorithm = strtolower(phpseclib_mcrypt_enc_get_algorithms_name($td));
        return phpseclib_mcrypt_module_get_supported_key_sizes($algorithm);
    }

    /**
     * Returns if the specified module is a block algorithm or not
     *
     * This function returns TRUE if the mode is for use with block algorithms, otherwise it returns FALSE. (e.g. FALSE for stream, and TRUE for cbc, cfb, ofb).
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_algorithm_mode($mode, $lib_dir = '')
    {
        switch ($mode) {
            case 'cbc':
            case 'ctr':
            case 'ecb':
            case 'ncfb':
            case 'nofb':
                return true;
        }
        return false;
    }

    /**
     * This function checks whether the specified algorithm is a block algorithm
     *
     * This function returns TRUE if the specified algorithm is a block algorithm, or FALSE if it is a stream one.
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_algorithm($algorithm, $lib_dir = '')
    {
        switch ($algorithm) {
            case 'rijndael-128':
            case 'twofish':
            case 'rijndael-192':
            case 'blowfish-compat':
            case 'des':
            case 'rijndael-256':
            case 'blowfish':
            case 'rc2':
            case 'tripledes':
                return true;
        }
        return false;
    }

    /**
     * Returns if the specified mode outputs blocks or not
     *
     * This function returns TRUE if the mode outputs blocks of bytes or FALSE if it outputs just bytes. (e.g. TRUE for cbc and ecb, and FALSE for cfb and stream).
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_mode($mode, $lib_dir = '')
    {
        switch ($mode) {
            case 'cbc':
            case 'ecb':
                return true;
        }
        return false;
    }

    /**
     * This function runs a self test on the specified module
     *
     * This function runs the self test on the algorithm specified.
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_self_test($algorithm, $lib_dir = '')
    {
        return in_array($algorithm, phpseclib_mcrypt_list_algorithms());
    }

    /**
     * Encrypt / decrypt data
     *
     * Performs checks common to both mcrypt_encrypt and mcrypt_decrypt
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv
     * @param string $op
     * @return string|bool
     * @access private
     */
    function phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, $op)
    {
        // PHP 5.6 made mcrypt_encrypt() a lot less tolerant of bad input but it neglected to change
        // anything about mcrypt_generic(). and despite the changes insufficiently long plaintext
        // is still accepted.
        $keyLen = strlen($key);
        $sizes = phpseclib_mcrypt_module_get_supported_key_sizes($cipher);
        if (count($sizes) && !in_array($keyLen, $sizes)) {
            trigger_error(
                'mcrypt_' . $op . '(): Key of size ' . $keyLen . ' not supported by this algorithm. Only keys of size ' .
                preg_replace('#, (\d+)$#', ' or $1', implode(', ', $sizes)) . ' supported',
                E_USER_WARNING
            );
            return false;
        }
        $td = @phpseclib_mcrypt_module_open($cipher, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_encrypt(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        $maxKeySize = phpseclib_mcrypt_enc_get_key_size($td);
        if (!count($sizes) && $keyLen > $maxKeySize) {
            trigger_error(
                'mcrypt_' . $op . '(): Key of size ' . $keyLen . ' not supported by this algorithm. Only keys of size 1 to ' . $maxKeySize . ' supported',
                E_USER_WARNING
            );
            return false;
        }
        $iv_size = phpseclib_mcrypt_enc_get_iv_size($td);
        if (!isset($iv) && $iv_size) {
            trigger_error(
                'mcrypt_' . $op . '(): Encryption mode requires an initialization vector of size  ' . $iv_size,
                E_USER_WARNING
            );
            return false;
        }
        if (strlen($iv) != $iv_size) {
            trigger_error(
                'mcrypt_' . $op . '(): Received initialization vector of size  ' . strlen($iv) . ', but size ' . $iv_size . ' is required for this encryption mode',
                E_USER_WARNING
            );
            return false;
        }
        phpseclib_mcrypt_generic_init($td, $key, $iv);
        return $op == 'encrypt' ? phpseclib_mcrypt_generic($td, $data) : phpseclib_mdecrypt_generic($td, $data);
    }

    /**
     * Encrypts plaintext with given parameters
     *
     * Encrypts the data and returns it.
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv optional
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_encrypt($cipher, $key, $data, $mode, $iv = NULL)
    {
        return phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, 'encrypt');
    }

    /**
     * Decrypts crypttext with given parameters
     *
     * Decrypts the data and returns the unencrypted data.
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv optional
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_decrypt($cipher, $key, $data, $mode, $iv = NULL)
    {
        return phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, 'decrypt');
    }
}

// define
if (!function_exists('mcrypt_list_algorithms')) {
    function mcrypt_list_algorithms($lib_dir = '')
    {
        return phpseclib_mcrypt_list_algorithms($lib_dir);
    }

    function mcrypt_list_modes($lib_dir = '')
    {
        return phpseclib_mcrypt_list_modes($lib_dir);
    }

    function mcrypt_create_iv($size, $source = MCRYPT_DEV_URANDOM)
    {
        return phpseclib_mcrypt_create_iv($size, $source);
    }

    function mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory)
    {
        return phpseclib_mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory);
    }

    function mcrypt_enc_get_key_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_key_size($td);
    }

    function mcrypt_enc_get_iv_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_iv_size($td);
    }

    function mcrypt_enc_get_block_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_block_size($td);
    }

    function mcrypt_generic_init(Base $td, $key, $iv)
    {
        return phpseclib_mcrypt_generic_init($td, $key, $iv);
    }

    function mcrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic($td, $data);
    }

    function mcrypt_generic_deinit(Base $td)
    {
        return phpseclib_mcrypt_generic_deinit($td);
    }

    function mcrypt_module_close(Base $td)
    {
        return phpseclib_mcrypt_module_close($td);
    }

    function mdecrypt_generic(Base $td, $data)
    {
        return phpseclib_mdecrypt_generic($td, $data);
    }

    function mcrypt_enc_get_algorithms_name(Base $td)
    {
        return phpseclib_mcrypt_enc_get_algorithms_name($td);
    }

    function mcrypt_enc_get_modes_name(Base $td)
    {
        return phpseclib_mcrypt_enc_get_modes_name($td);
    }

    function mcrypt_enc_is_block_algorithm_mode(Base $td)
    {
        return phpseclib_mcrypt_enc_is_block_algorithm_mode($td);
    }

    function mcrypt_enc_is_block_algorithm(Base $td)
    {
        return phpseclib_mcrypt_enc_is_block_algorithm($td);
    }

    function mcrypt_enc_self_test(Base $td)
    {
        return phpseclib_mcrypt_enc_self_test($td);
    }

    function mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir);
    }

    function mcrypt_encrypt($cipher, $key, $data, $mode, $iv = NULL)
    {
        return phpseclib_mcrypt_encrypt($cipher, $key, $data, $mode, $iv);
    }

    function mcrypt_module_get_algo_block_size($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_algo_block_size($algorithm, $lib_dir);
    }

    function mcrypt_module_get_algo_key_size($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_algo_key_size($algorithm, $lib_dir);
    }

    function mcrypt_enc_get_supported_key_sizes(Base $td)
    {
        return phpseclib_mcrypt_enc_get_supported_key_sizes($td);
    }

    function mcrypt_module_is_block_algorithm_mode($mode, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_is_block_algorithm_mode($mode, $lib_dir);
    }

    function mcrypt_module_is_block_algorithm($algorithm, $lib_dir=  '')
    {
        return phpseclib_mcrypt_module_is_block_algorithm($algorithm, $lib_dir);
    }

    function mcrypt_module_is_block_mode($mode, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_is_block_mode($mode, $lib_dir);
    }

    function mcrypt_module_self_test($algorithm, $lib_dir = '')
    {
        return mcrypt_module_self_test($algorithm, $lib_dir);
    }

    function mcrypt_decrypt($cipher, $key, $data, $mode, $iv = NULL)
    {
        return phpseclib_mcrypt_decrypt($cipher, $key, $data, $mode, $iv);
    }
}
