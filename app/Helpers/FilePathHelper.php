 <?php

    function base_url($path = '')
    {
        return url(ltrim($path, '/'));
    }


    function asset_dir($path, $use = 'external')
    {
        $relativePath = trim($path, '/');
        $absolutePath = public_path($relativePath);


        if (!is_dir($absolutePath)) {
            mkdir($absolutePath, 0755, true);
        }


        if ($use === 'internal') {
            return $absolutePath;
        }


        if ($use === 'relative') {
            return $relativePath;
        }


        if ($use === 'external') {
            return rtrim(base_url($relativePath), '/') . '/';
        }


        return null;
    }


    function get_file_url($path, $fileName)
    {
        if (!$fileName) return null;

        return $path . $fileName;
    }


    function franchise_asset_dir($franchise_name, $use = 'external')
    {
        $franchise_name = trim($franchise_name, '/');
        $relativePath = 'assets/franchises/' . $franchise_name;
        return asset_dir($relativePath, $use);
    }


    function default_user_img()
    {
        return base_url('assets/user_placeholder.png');
    }


    function default_blank_img()
    {
        return base_url('assets/image_placeholder.png');
    }


    function unlink_file($path)
    {
        if (!empty($path) && file_exists($path) && is_file($path)) {
            unlink($path);
        }
    }
