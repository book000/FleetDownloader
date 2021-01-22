declare module '*/config.json' {
    interface Config {
        username: string;
        password: string;
    }

    const value: Config;
    export = value;
}