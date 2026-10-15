# V4 Package build manifest notes

- v3 source file count: 100. v3 input directory unmodified: True.
- v4 Markdown files before this notes file: 118.
- The original upstream source is installed by Git clone on Windows, **not embedded in ZIP**. All 59 Git repo files become available in local source checkout; six original skills are copied byte-identically.
- A local verifier compares SHA256 of every copied skill file with the pinned source. Script has **not** been executed on user's machine or tested in PowerShell here.
- The Laravel application has not been opened or changed by this package.
