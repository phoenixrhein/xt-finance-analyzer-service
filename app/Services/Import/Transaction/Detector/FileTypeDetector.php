<?php

namespace de\xovatec\financeAnalyzer\Services\Import\Transaction\Detector;

use InvalidArgumentException;

class FileTypeDetector
{
    /**
     *
     * @var array
     */
    private array $detectors;

    /**
     *
     * @param array $detectors
     */
    public function __construct(array $detectors)
    {
        foreach ($detectors as $detector) {
            if (!$detector instanceof FileFormatDetectorInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Detector must be an instance of %s, %s given.',
                    FileFormatDetectorInterface::class,
                    is_object($detector) ? get_class($detector) : gettype($detector)
                ));
            }
        }

        $this->detectors = $detectors;
    }

    /**
     *
     * @param string $filePath
     * @return FileFormatDetectorInterface
     */
    public function detect(string $filePath): FileFormatDetectorInterface
    {
        foreach ($this->detectors as $detector) {
            if ($detector->supports($filePath)) {
                return $detector;
            }
        }

        throw new InvalidArgumentException("No supported file format detector for MIME type: {$filePath}");
    }
}
