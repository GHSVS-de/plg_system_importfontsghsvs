# plg_system_importfontsghsvs

- Joomla-System-Plugin.
- Import and save G WebFonts and browser dependent CSS via server IP and **not** page visitor IP.
- Load them on-the-fly.
- Joomla's `/media/` folder or at least `/media/plg_sytem_importfonts/font/` must be writable for PHP (Joomla).
- Uses modified PHP class [intekhabrizvi/cssparser](https://github.com/intekhabrizvi/cssparser)

-----------------------------------------------------

# My personal build procedure (WSL 1, Debian, Win 10)

**@since v2022.06.20: Build procedure uses local repo fork of https://github.com/GHSVS-de/buildKramGhsvs**

- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_importfontsghsvs`

## node/npm updates/installation
- `npm install` (if never done before)

### Update dependencies
- `npm run updateCheck` or (faster) `npm outdated`
- `npm run update` (if needed) or (faster) `npm update --save-dev`

## PHP Codestyle
If you think it's worth it.
- `cd /mnt/z/git-kram/php-cs-fixer-ghsvs`
- `npm run plg_system_importfontsghsvssDry` (= dry test run).
- `npm run plg_system_importfontsghsvs` (= cleans code).
- `cd /mnt/z/git-kram/plg_system_importfontsghsvs` (back to this repo).

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.

### For Joomla update and changelog server
- Create new release with new tag.
- - See and copy and complete release description in `dist/release.txt`.
- Extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Copy/paste and make necessary additions.
