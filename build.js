#!/usr/bin/env node
const path = require('path');

/* Configure START */
const pathBuildKram = path.resolve("../buildKramGhsvs");
const updateXml = `${pathBuildKram}/build/update_no-changelog.xml`;
// const changelogXml = `${pathBuildKram}/build/changelog.xml`;
const releaseTxt = `${pathBuildKram}/build/release_no-changelog.txt`;
/* Configure END */

const replaceXml = require(`${pathBuildKram}/build/replaceXml.js`);
const helper = require(`${pathBuildKram}/build/helper.js`);

const pc = require(`${pathBuildKram}/node_modules/picocolors`);
const fse = require(`${pathBuildKram}/node_modules/fs-extra`);

let replaceXmlOptions = {};
let zipOptions = {};
let from = "";
let to = "";

const {
	filename,
	name,
	version,
} = require("./package.json");

const manifestFileName = `${filename}.xml`;
const Manifest = `${__dirname}/package/${manifestFileName}`;
const pathMedia = `./media`;

(async function exec()
{
	let cleanOuts = [
		`./package`,
		`./dist`,
	];
	await helper.cleanOut(cleanOuts);

	from = pathMedia;
	to = `./package/media`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	from = `./src`;
	to = `./package`;
	await fse.copy(from, to
	).then(
		answer => console.log(
			pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
		)
	);

	await helper.mkdir('./dist');

	const zipFilename = `${name}-${version}.zip`;

	replaceXmlOptions = {
		"xmlFile": Manifest,
		"zipFilename": zipFilename,
		"checksum": "",
		"dirname": __dirname
	};

	await replaceXml.main(replaceXmlOptions);
	await fse.copy(`${Manifest}`, `./dist/${manifestFileName}`).then(
		answer => console.log(pc.yellow(pc.bold(
			`Copied "${manifestFileName}" to "./dist".`)))
	);

	// ## Create zip file and detect checksum then.
	const zipFilePath = path.resolve(`./dist/${zipFilename}`);

	zipOptions = {
		"source": path.resolve("package"),
		"target": zipFilePath
	};
	await helper.zip(zipOptions)

	const Digest = 'sha256'; //sha384, sha512
	const checksum = await helper.getChecksum(zipFilePath, Digest)
  .then(
		hash => {
			const tag = `<${Digest}>${hash}</${Digest}>`;
			console.log(pc.green(pc.bold(`Checksum tag is: ${tag}`)));
			return tag;
		}
	)
	.catch(error => {
		console.log(error);
		console.log(pc.red(pc.bold(
			`Error while checksum creation. I won't set one!`)));
		return '';
	});

	replaceXmlOptions.checksum = checksum;

	// Bei diesen werden zuerst Vorlagen nach dist/ kopiert und dort erst "replaced".
	for (const file of [updateXml, releaseTxt])
	{
		from = file;
		to = `./dist/${path.win32.basename(file)}`;
		await fse.copy(from, to
		).then(
			answer => console.log(
				pc.yellow(pc.bold(`Copied "${from}" to "${to}".`))
			)
		);

		replaceXmlOptions.xmlFile = path.resolve(to);
		await replaceXml.main(replaceXmlOptions);
	}

	cleanOuts = [
		`./package`,
	];
	await helper.cleanOut(cleanOuts).then(
		answer => console.log(pc.cyan(pc.bold(pc.bgRed(
			`Finished. Good bye!`))))
	);
})();
