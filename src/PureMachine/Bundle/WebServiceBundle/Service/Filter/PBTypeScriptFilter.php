<?php
namespace PureMachine\Bundle\WebServiceBundle\Service\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\DependencyExtractorInterface;
use Assetic\Exception\FilterException;
use Assetic\Filter\BaseNodeFilter;
use Assetic\Factory\AssetFactory;
use Symfony\Component\Finder\Finder;

class PBTypeScriptFilter extends BaseNodeFilter implements DependencyExtractorInterface
{
    private $tscBin;
    private $nodeBin;

    public function __construct($tscBin = '/usr/bin/tsc', $nodeBin = null)
    {
        $this->tscBin = $tscBin;
        $this->nodeBin = $nodeBin;
    }

    public function filterLoad(AssetInterface $asset)
    {
        /*
         * Only if references.ts
         */
        $templateName = basename($asset->getSourcePath());
        if ($templateName != 'references.ts') {
            return;
        }

        $pb = $this->createProcessBuilder($this->nodeBin
            ? array($this->nodeBin, $this->tscBin)
            : array($this->tscBin));

        $template = $asset->getSourceRoot().DIRECTORY_SEPARATOR.$asset->getSourcePath();
        $outputPath = tempnam(sys_get_temp_dir(), 'output');

        $pb->add('--removeComments')->add($template)->add('--out')->add($outputPath);

        $proc = $pb->getProcess();
        $code = $proc->run();

        if (0 !== $code) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $compiledJs = file_get_contents($outputPath);
        unlink($outputPath);

        $asset->setContent($compiledJs);
    }

    public function filterDump(AssetInterface $asset)
    {
    }

    /**
     * To invalidate the cache, assetic check only the ressource file
     * but we need to check every files of the directory
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $children = array();
        $finder = new Finder();
        $iterator = $finder->files()->name('*.ts')->in(array($loadPath));
        $ref = $loadPath.DIRECTORY_SEPARATOR."references.ts";
        $mtime = 0;

        foreach ($iterator as $file) {
            $leafs = $factory->createAsset($file->getPathName(), array(), array('root' => $loadPath));
            foreach ($leafs as $leaf) {
                $children[] = $leaf;
            }

            $newntime = filemtime($file->getPathName());
            if ($newntime > $mtime) {
                $mtime = $newntime;
            }
        }

        /**
         * Touch the main file to force regeneration
         */
        if ($mtime > filemtime($ref)) {
            touch($ref);
        }

        return $children;
    }
}
