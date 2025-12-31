# Audio Files for Worker Calls Display

This directory contains audio notification files for the Worker Calls Display screen.

## Required File

- `ding.mp3` - A simple notification sound (0.5-1 second duration, < 50KB)

## Usage

The audio file is played when:
- A new worker call appears
- A call status changes from `pending` to `staff_called`

## Adding the Audio File

Place a `ding.mp3` file in this directory. The file should be:
- Format: MP3
- Duration: 0.5-1 second
- Size: < 50KB
- Simple "ding" or bell sound

## Alternative

If you don't have an audio file, the system will work without it (audio play will fail silently).

