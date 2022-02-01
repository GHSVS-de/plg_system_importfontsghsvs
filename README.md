# plg_system_importfontsghsvs

- Joomla-System-Plugin.
- Import and save G WebFonts and browser dependent CSS via server IP and **not** page visitor IP.
- Load them on-the-fly.
- Joomla's `/media/` folder or at least `/media/plg_sytem_importfonts/font/` must be writable for PHP (Joomla).
- Uses modified PHP class [intekhabrizvi/cssparser](https://github.com/intekhabrizvi/cssparser)

-----------------------------------------------------

# My personal build procedure (WSL 1, Debian, Win 10)
- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_importfontsghsvs`

## node/npm installation/updates
- If not done yet: `npm install`

### Update
- `npm run g-npm-update-check` or (faster) `npm outdated"`
- `npm run g-npm-update` (if needed) or (faster) `npm update --save-dev`

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.

### For Joomla update and changelog server
- Create new release with new tag.
- - See and copy and complete release description in `dist/release.txt`.
- Extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Copy/paste and make necessary additions.
