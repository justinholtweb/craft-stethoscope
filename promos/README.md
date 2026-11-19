# Plugin Store promo images

Marketing images for the Stethoscope listing on the Craft Plugin Store, in the same theme as
[justinholt.com/plugins/craft-stethoscope](https://justinholt.com/plugins/craft-stethoscope).

```bash
./build.sh          # all slides
./build.sh "2 5"    # just slides 2 and 5
```

Output: `out/stethoscope-promo-N.jpg`, 1920×1080 (rendered at 2× in headless Chrome, downsampled).
**JPEG, never PNG.** `fonts.css` is generated and gitignored.

The cover and the closing slide carry the Craft 6 beta caution, and every footer says
"Craft CMS 6 (beta)" — keep it there until Craft 6 and Stethoscope leave beta. Figures on the slides
are illustrative.

| # | Slide |
| --- | --- |
| 1 | Cover — free, Laravel Pulse, Craft 6 beta caution |
| 2 | The N+1 finder |
| 3 | Template renders and the timing tag (Twig and Blade) |
| 4 | Nine Craft cards |
| 5 | Every number, per site |
| 6 | Private by default |
| 7 | Closing — summary, beta caution |
