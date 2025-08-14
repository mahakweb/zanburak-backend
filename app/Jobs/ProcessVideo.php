<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        $disk = $this->video->disk;
        $rawPath = $this->video->path;
        $videoable = $this->video->videoable;
        $slug = $videoable->slug ?? 'unknown';

        $media = FFMpeg::fromDisk($disk)->open($rawPath);
        $dimensions = $media->getVideoStream()->getDimensions();
        $maxHeight = $dimensions->getHeight();

        $availableQualities = collect([1080, 720, 480, 360, 240, 144])
            ->filter(fn($q) => $q <= $maxHeight)
            ->values();

        if ($videoable instanceof \App\Models\Course) {
            $streamPath = "stream/{$slug}";
            $downloadPath = "download/{$slug}";
            $trailerPath = "trailer/{$slug}";
            $this->processTrailer($disk, $rawPath, $trailerPath, $availableQualities);
        } elseif ($videoable instanceof \App\Models\Episode) {
            $episodeId = $videoable->id;
            $streamPath = "stream/{$videoable->section->course->slug}/{$episodeId}"; // $videoable->section->course->slug because $slug just return slug of episode and next id episode  ex: introdusction/1 
            $downloadPath = "download/{$videoable->section->course->slug}/{$episodeId}"; // $videoable->section->course->slug because $slug just return slug of episode and next id episode  ex: introdusction/1 


            $this->processStream($disk, $rawPath, $streamPath, $availableQualities);
            foreach ($availableQualities as $quality) {
                $this->processDownload($disk, $rawPath, "$downloadPath/{$quality}p", $quality);
            }
        } else {
            return;
        }


    }

    protected function processTrailer($disk, $inputPath, $outputPath, $availableQualities)
    { 
        set_time_limit(0);
        ini_set('max_execution_time', 1800);

        $hlsPath = "{$outputPath}";
        $masterPlaylistPath = "{$hlsPath}/playlist.m3u8";

        $exporter = FFMpeg::fromDisk($disk)
            ->open($inputPath)
            ->exportForHLS()
            ->toDisk($disk)
            ->setSegmentLength(10); 

        foreach ($availableQualities as $quality) {
            $folder = "{$quality}p";
            $bitrate = match ($quality) {
                144 => 200,
                240 => 300,
                360 => 500,
                480 => 1000,
                720 => 1500,
                1080 => 3000,
                default => 1500,
            };

            $dimensions = match ($quality) {
                144 => [256, 144],
                240 => [426, 240],
                360 => [640, 360],
                480 => [854, 480],
                720 => [1280, 720],
                1080 => [1920, 1080],
                default => [1280, 720],
            };

            [$width, $height] = $dimensions;

            $format = (new X264('aac'))->setKiloBitrate($bitrate);

            $exporter->addFormat($format, function ($media) use ($width, $height) {
                $media->scale($width, $height);
                $media->addWatermark(function (WatermarkFactory $watermark) {
                    $watermark->fromDisk('public')
                        ->open('logo-with-text.png')
                        ->right(15)
                        ->bottom(15);
                });
            }, "{$folder}/media.m3u8");
        }

        $exporter->save($masterPlaylistPath);

        $this->storeVideoRecord('trailer', null, $masterPlaylistPath);
    }


    protected function processStream($disk, $inputPath, $outputPath, $availableQualities)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 1800);

        $hlsPath = "{$outputPath}";
        $masterPlaylistPath = "{$hlsPath}/playlist.m3u8";

        $exporter = FFMpeg::fromDisk($disk)
            ->open($inputPath)
            ->exportForHLS()
            ->toDisk($disk)
            ->setSegmentLength(10)
            ->withRotatingEncryptionKey(function ($filename, $contents) use ($disk, $hlsPath) {
                Storage::disk($disk)->put("{$hlsPath}/keys/{$filename}", $contents);
            });

        foreach ($availableQualities as $quality) {
            $folder = "{$quality}p"; // e.g., 360p
            $bitrate = match ($quality) {
                144 => 200,
                240 => 300,
                360 => 500,
                480 => 1000,
                720 => 1500,
                1080 => 3000,
                default => 1500,
            };

            $dimensions = match ($quality) {
                144 => [256, 144],
                240 => [426, 240],
                360 => [640, 360],
                480 => [854, 480],
                720 => [1280, 720],
                1080 => [1920, 1080],
                default => [1280, 720],
            };

            [$width, $height] = $dimensions;

            $format = (new X264('aac'))->setKiloBitrate($bitrate);

            $exporter->addFormat($format, function ($media) use ($width, $height) {
                $media->scale($width, $height);
                $media->addWatermark(function (WatermarkFactory $watermark) {
                    $watermark->fromDisk('public')
                        ->open('logo-with-text.png')
                        ->right(15)
                        ->bottom(15);
                });
            }, "{$folder}/media.m3u8");
        }

        $exporter->save($masterPlaylistPath);

        $this->storeVideoRecord('stream', null, $masterPlaylistPath);
    }

    protected function processDownload($disk, $inputPath, $outputPath, $quality)
    {
        $storedPath = "$outputPath/video-{$quality}p.mp4";
        $bitrate = match ($quality) {
            144 => 200,
            240 => 300,
            360 => 500,
            480 => 1000,
            720 => 1500,
            1080 => 3000,
            default => 1500,
        };

        FFMpeg::fromDisk($disk)
            ->open($inputPath)
            ->export()
            ->toDisk($disk)
            ->inFormat((new X264('aac'))->setKiloBitrate($bitrate))
            ->addWatermark(function (WatermarkFactory $watermark) {
                $watermark->fromDisk('public')
                    ->open('logo-with-text.png')
                    ->right(10)
                    ->bottom(10);
            })
            ->save($storedPath);

        $this->storeVideoRecord('download', $quality, $storedPath);
    }




    protected function storeVideoRecord($type, $quality, $path)
    {
        $this->video->videoable->videos()->create([
            'type' => $type,
            'quality' => $quality,
            'path' => $path,
            'duration' => $this->video->duration,
            'disk' => $this->video->disk,
        ]);
    }
}
