<?php

namespace App\Services;

use App\Exceptions\FileRejectedException;
use App\Models\TalosFile;
use Illuminate\Http\UploadedFile;

class FileUploadService
{
    // Any extension that could be executed on a server or OS is blocked.
    private const BLOCKED_EXTENSIONS = [
        // OS executables
        'exe', 'com', 'bat', 'cmd', 'msi', 'msp', 'mst', 'scr', 'pif', 'vxd',
        // Shell scripts
        'sh', 'bash', 'zsh', 'fish', 'ksh', 'csh',
        // Windows scripts
        'ps1', 'psm1', 'psd1', 'vbs', 'vbe', 'vbscript', 'wsf', 'wsh', 'hta', 'lnk',
        // Web/server-side
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'asp', 'aspx', 'ashx', 'asmx', 'ascx', 'cshtml',
        'jsp', 'jspx', 'jsf',
        'cfm', 'cfc', 'cfml',
        'cgi', 'fcgi', 'pl', 'pm',
        // Interpreted languages
        'py', 'pyc', 'pyo', 'pyw',
        'rb', 'rbw',
        'r',
        // Compiled/bytecode
        'class', 'jar', 'war', 'ear',
        // Native libraries
        'dll', 'so', 'dylib', 'lib',
        // OS packages
        'deb', 'rpm', 'pkg', 'dmg', 'app', 'ipa', 'apk',
        // Registry / system
        'reg', 'cpl', 'msc', 'inf', 'ins',
    ];

    private const BLOCKED_MIMES = [
        'application/x-executable',
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-sh',
        'application/x-shellscript',
        'application/x-php',
        'application/php',
        'text/x-php',
        'application/x-httpd-php',
        'application/x-perl',
        'application/x-ruby',
        'application/x-python',
        'application/x-bytecode.python',
        'application/x-java-applet',
        'application/java-archive',
    ];

    public function __construct(private StorageSettings $storage) {}

    /**
     * @throws FileRejectedException
     */
    public function store(UploadedFile $uploaded): TalosFile
    {
        $ext = strtolower($uploaded->getClientOriginalExtension());

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw new FileRejectedException('File type not allowed.');
        }

        // Validate MIME type using the actual file content, not the client header.
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($uploaded->getRealPath());

        if ($this->isMimeDangerous($realMime)) {
            throw new FileRejectedException('File type not allowed.');
        }

        $hash = md5_file($uploaded->getRealPath());

        // Store without extension so the file can never be executed by the web server.
        $path = 'talos/files/' . $hash;

        $disk = $this->storage->mediaDisk();
        $disk->put($path, file_get_contents($uploaded->getRealPath()));

        $diskName = $this->storage->isR2MediaEnabled() ? 'r2' : 'public';

        return TalosFile::create([
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type'     => $realMime,
            'ext'           => $ext,
            'size'          => $uploaded->getSize(),
            'path'          => $path,
            'disk'          => $diskName,
            'hash'          => $hash,
        ]);
    }

    private function isMimeDangerous(string $mime): bool
    {
        return in_array($mime, self::BLOCKED_MIMES, true)
            || str_contains($mime, 'x-executable')
            || str_contains($mime, 'x-shellscript');
    }
}
