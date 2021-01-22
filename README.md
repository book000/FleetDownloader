# FleetDownloader

Get the Twitter Fleet and download it.

Inspire/Based from [furyutei/TwFleetCapture](https://github.com/furyutei/TwFleetCapture)

## Requirements

- NodeJS (Tested with v15.4.0)
- NPM (Tested with v7.0.15)
- [packages.json](packages.json): `typescript`, `puppeteer`

## Installation

1. Clone from GitHub repository: `git clone https://github.com/book000/FleetDownloader.git`
2. Install the dependency package from `packages.json`: `npm i`
3. Compile typescript files: `npm run build` or `tsc`

## Configuration

Rewrite `config.sample.json` and rename to `config.json`.

## Usage

```shell
cd /path/to/
node out/main.js
```

`out/main.js` gets the parent directory `config.json`. Therefore, create `config.json` in the root directory of project.

When executed, the data will be output to `fleetline.json` in the project root directory. And, outputs  fleet user data to the `users` directory.

## Usage-Examples

- Examples: [/examples/](examples/)

See the [README.md in examples directory](examples/README.md) for a detailed explanation.

## Warning / Disclaimer

The developer is not responsible for any problems caused by the user using this project.

## License

The license for this project is [MIT License](LICENSE).
