<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    // private $targetDirectory;
    // private $slugger;

    // public function __construct($targetDirectory, SluggerInterface $slugger)
    // {
    //     $this->targetDirectory = $targetDirectory;
    //     $this->slugger = $slugger;
    // }

    // public function upload(UploadedFile $file): string
    // {
    //     $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //     $safeFilename = $this->slugger->slug($originalFilename);
    //     $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

    //     try {
    //         $file->move($this->getTargetDirectory(), $newFilename);
    //     } catch (FileException $e) {
    //         throw new \Exception("File upload failed");
    //     }

    //     return 'media/' . $newFilename;
    // }

    // public function delete(string $filename): void
    // {
    //     $filePath = $this->targetDirectory . '/' . basename($filename);
    //     if (file_exists($filePath)) {
    //         unlink($filePath);
    //     }
    // }

    // private function getTargetDirectory()
    // {
    //     return $this->targetDirectory;
    // }
}
