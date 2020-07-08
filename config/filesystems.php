<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('S3_ACCESS_KEY_ID'),
            'secret' => env('S3_SECRET_ACCESS_KEY'),
            'region' => env('S3_DEFAULT_REGION'),
            'bucket' => env('S3_BUCKET_API'),
        ],

        's3_backup' => [
            'driver' => 's3',
            'key' => env('S3_ACCESS_KEY_ID'),
            'secret' => env('S3_SECRET_ACCESS_KEY'),
            'region' => env('S3_DEFAULT_REGION'),
            'bucket' => env('S3_BUCKET_BACKUP'),
        ],

        'public_custom' => [
            'driver' => 'local',
            'root' => public_path('files'),
        ],

        'public_custom' => [
            'driver' => 'local',
            'root' => public_path('files'),
        ],

        'oss' => [
            'driver'        => 'oss',
            'access_id'     => env('ALI_ACCESS_ID'),
            'access_key'    => env('ALI_ACCESS_KEY'),
            'bucket'        => env('ALI_BUCKET_API'), // ini disesuaikan mau write di bucket api atau view untuk config yg ini
            'endpoint'      => env('ALI_ENDPOINT'), // OSS Extranet node or custom external domain name
            //'endpoint_internal' => '<internal endpoint [OSS Intranet node] as：oss-cn-shenzhen-internal.aliyuncs.com>', // v2.0.4 New configuration attribute, if it is empty, the default endpoint configuration is used (because the internal network upload is a little unresolved, please do not use the intranet node to upload for the time being, it is in communication with Alibaba Technology)
            'cdnDomain'     => '', // if is CName is true, getUrl will determine whether cdn Domain is set to determine the returned URL，If cdnDomain is not set，Then use endpoint to generate url，Otherwise use cdn
            'ssl'           => true, // true to use 'https://' and false to use 'http://'. default is false,
            'isCName'       => false, // Whether to use a custom domain name,true: Then Storage.url()Will use custom CDN or domain name to generate file url， false: Then use an external node to generate the url
            'debug'         => true
        ],

        'gcs' => [
            'driver' => 'gcs',
            'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'),
            'key_file' => base_path() . env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
            'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', null), // optional: /default/path/to/apply/in/bucket
            'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
            'visibility' => 'public', // optional: public|private
        ],
    ],

];
