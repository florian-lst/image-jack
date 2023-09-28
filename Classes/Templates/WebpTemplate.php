<?php
declare(strict_types=1);

namespace Sitegeist\ImageJack\Templates;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WebpTemplate extends AbstractTemplate implements TemplateInterface
{
    public function isAvailable(): bool
    {
        $mimeTypes = [];
        foreach ($this->extensionConfiguration['webp']['mimeTypes'] as $key => $value) {
            if ($value) {
                $mimeTypes[] = 'image/' . $key;
            }
        }

        return (in_array($this->image->getMimeType(), $mimeTypes) && $this->extensionConfiguration['webp']['active']);
    }

    public function processFile(): void
    {
        $converter = $this->extensionConfiguration['webp']['converter'] ?: 'im';
        $options = $this->extensionConfiguration['webp']['options'] ?:
            '-quality 75 -define webp:lossless=false -define webp:method=6';

        switch ($converter) {
            case 'gd':
                $buffer = $this->convertImageUsingGd($options);
                break;

            case 'ext':
                $buffer = $this->convertImageUsingExt($options);
                break;

            default:
                $buffer = $this->convertImageUsingIm($options);
                break;
        }

        GeneralUtility::fixPermissions($this->imagePath . '.webp');

        if (!empty($buffer)) {
            $this->logger->writeLog(trim($buffer), LogLevel::INFO);
        }
    }

    /**
     * @param string $options
     * @return string
     */
    protected function convertImageUsingIm(string $options)
    {
        $graphicalFunctionsObject = GeneralUtility::makeInstance(GraphicalFunctions::class);
        return $graphicalFunctionsObject->imageMagickExec(
            $this->imagePath,
            $this->imagePath . '.webp',
            $options
        );
    }

    /**
     * @param string $quality
     * @return bool
     */
    protected function convertImageUsingGd(string $quality)
    {
        if (function_exists('imagewebp') && defined('IMG_WEBP') && (imagetypes() & IMG_WEBP) === IMG_WEBP) {
            $graphicalFunctionsObject = GeneralUtility::makeInstance(GraphicalFunctions::class);
            $image = $graphicalFunctionsObject->imageCreateFromFile($this->imagePath);
            // Convert CMYK to RGB
            if (!imageistruecolor($image)) {/* @phpstan-ignore-line */
                imagepalettetotruecolor($image);/* @phpstan-ignore-line */
            }

            return imagewebp($image, $this->imagePath . '.webp', (int)$quality);/* @phpstan-ignore-line */
        } else {
            $this->logger->writeLog('Webp is not supported by your GD version', LogLevel::ERROR);
        }

        return false;
    }

    /**
     * @param string $command
     * @return string
     */
    protected function convertImageUsingExt(string $command)
    {
        if (substr_count($command, '%s') !== 2) {
            $this->logger->writeLog('Please use two placeholders in your command', LogLevel::ERROR);
        }
        $binary = explode(' ', $command)[0];
        if (!is_executable($binary)) {
            $this->logger->writeLog(
                sprintf('Binary "%s" is not executable! Please use the full path to the binary.', $binary),
                LogLevel::ERROR
            );
            return 'Error! See log for further information.';
        }

        $this->logger->writeLog($command, LogLevel::INFO);
        return CommandUtility::exec(sprintf(
            escapeshellcmd($command),
            CommandUtility::escapeShellArgument($this->imagePath),
            CommandUtility::escapeShellArgument($this->imagePath . '.webp')
        ) . ' >/dev/null 2>&1');
    }
}
