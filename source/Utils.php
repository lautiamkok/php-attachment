<?php
namespace Monsoon;

trait Utils
{
    // Convert the $_FILES array to the cleaner (IMHO) array.
    // http://php.net/manual/en/features.file-upload.multiple.php#53240
    function reArrayUploadFiles (&$file_post)
    {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i=0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

    // Validate the file array.
    function validateUploadFiles (
        $file = '',
        $maxsize = 2,
        $whitelist = []
    ) {
        // File upload error messages.
        // http://php.net/manual/en/features.file-upload.errors.php
        $phpFileUploadErrors = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];

        $max_upload_size = $maxsize * 1024 * 1024; // 2MB

        // Use the default whitelist if it is not provided.
        // https://www.sitepoint.com/web-foundations/mime-types-summary-list/
        if (count($whitelist) === 0) {
            $whitelist = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'video/mpeg',
                'video/mp4',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/pdf'
            ];
        }

        $result = [];
        $result['error'] = 0;

        if ($file['error']) {
            $result['name'] = $file['name'];
            $result['error'] = $phpFileUploadErrors[$file['error']];
            return $result;
        }

        if (!in_array($file['type'], $whitelist)) {
            $result['name'] = $file['name'];
            $result['error'] = 'must be a jpeg, or png';
        } elseif(($file['size'] > $max_upload_size)){
            $result['name'] = $file['name'];
            $result['error'] = $this->convertToReadableSize($file['size']) . ' bytes! It must not exceed ' . $this->convertToReadableSize($max_upload_size) . ' bytes.';
        }
        return $result;
    }

    // Byte to readable format.
    // https://subinsb.com/convert-bytes-kb-mb-gb-php/
    function convertToReadableSize($size)
    {
      $base = log($size) / log(1024);
      $suffix = array("", "KB", "MB", "GB", "TB");
      $f_base = floor($base);
      return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    // Get bytes.
    function iniGetBytes($val)
    {
        $val = trim(ini_get($val));
        if ($val != '') {
            $last = $val{strlen($val) - 1};
        } else {
            $last = '';
        }
        $val = str_replace($last, '', $val);
        $last = strtolower($last);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

    // Remove array the key that has empty value - recursively.
    // https://stackoverflow.com/questions/7696548/php-how-to-remove-empty-entries-of-an-array-recursively
    function arrayFilter($array)
    {
         if(!empty($array)) {
             return array_filter($array);
         }
    }

}

