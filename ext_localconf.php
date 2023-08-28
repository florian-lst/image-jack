<?php
defined('TYPO3') || die();

call_user_func(function () {

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['Sitegeist']['ImageJack']['writerConfiguration'] = [
        // configuration for ERROR level log entries
        \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
            // add a FileWriter
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                // configuration for the writer
                'logFileInfix' => 'image_jack'
            ],
        ]
    ];

    if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['image_jack']['useLiveProcessing'])
        && $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['image_jack']['useLiveProcessing']) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['ImageJackProcessor'] = [
            'className' => Sitegeist\ImageJack\Processor\ImageJackProcessor::class,
            'before' => ['LocalImageProcessor']
        ];
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['image_jack']['templates']['jpegOptimizer'] =
        \Sitegeist\ImageJack\Templates\JpegTemplate::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['image_jack']['templates']['pngOptimizer'] =
        \Sitegeist\ImageJack\Templates\PngTemplate::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['image_jack']['templates']['webpConverter'] =
        \Sitegeist\ImageJack\Templates\WebpTemplate::class;

    if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['Local'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['Local']['class']] = [
            'className' => \Sitegeist\ImageJack\Xclass\LocalDriver::class
        ];
    }

    if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['AusDriverAmazonS3'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['AusDriverAmazonS3']['class']] = [
            'className' => \Sitegeist\ImageJack\Xclass\AmazonS3Driver::class
        ];
    }
});
