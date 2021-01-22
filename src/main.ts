import puppeteer, { LaunchOptions } from "puppeteer";
import config from "../config.json";
import * as fs from 'fs';

const options: LaunchOptions = {
    headless: true,
    slowMo: 10,
    args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--lang=ja-JP"
    ],
    userDataDir: "./userdata"
};

(async () => {
    const browser = await puppeteer.launch(options);
    const page = await browser.newPage();

    await page.goto("https://twitter.com/", {
        waitUntil: ["load", "networkidle2"]
    });
    await page.waitForTimeout(5000);
    console.log("Connected to Twitter.");

    if (page.url() != "https://twitter.com/home") {
        console.log("You need to login again.");

        await page.goto("https://twitter.com/login", {
            waitUntil: ['load', 'networkidle2']
        });
        await page.waitForSelector("input[name='session[username_or_email]']", {
            visible: true
        }).then(elem => elem.type(config.username));
        await page.waitForSelector("input[name='session[password]']", {
            visible: true
        }).then(elem => elem.type(config.password));
        await page.waitForSelector("div[data-testid='LoginForm_Login_Button']", {
            visible: true
        }).then(elem => elem.click());

        await page.waitForTimeout(3000);

        if (page.url() != "https://twitter.com/home") {
            console.log("Failed to login.");
            await browser.close();
            process.exit(1);
        }
    }
    console.log("You have successfully logged in.");
    await page.waitForTimeout(5000);

    const results = await page.evaluate(async () => {
        const cookie = document.cookie.match(/ct0=(.*?)(?:;|$)/);
        const response = await fetch("https://api.twitter.com/fleets/v1/fleetline", {
            method: "GET",
            headers: {
                "authorization": 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA',
                "x-csrf-token": cookie != null ? cookie[1] : "",
                "x-twitter-active-user": "yes",
                "x-twitter-auth-type": "OAuth2Session",
                "x-twitter-client-language": "en",
            },
            mode: "cors",
            credentials: "include",
        });
        if (!response.ok) {
            throw new Error("Network response was not ok " + response.status);
        }
        return await response.json();
    });

    console.log(results);
    fs.writeFileSync(__dirname + "/fleetline.json", JSON.stringify(results));

    for (const thread of results["threads"]) {
        const user_id_str = thread["user_id_str"];
        console.log("fetch user", user_id_str)
        const result = await page.evaluate(async (user_id_str) => {
            const cookie = document.cookie.match(/ct0=(.*?)(?:;|$)/);
            const response = await fetch("https://api.twitter.com/1.1/users/show.json?user_id=" + user_id_str, {
                method: "GET",
                headers: {
                    "authorization": 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA',
                    "x-csrf-token": cookie != null ? cookie[1] : "",
                    "x-twitter-active-user": "yes",
                    "x-twitter-auth-type": "OAuth2Session",
                    "x-twitter-client-language": "en",
                },
                mode: "cors",
                credentials: "include",
            });
            if (!response.ok) {
                throw new Error("Network response was not ok " + response.status);
            }
            return await response.json();
        }, user_id_str);

        console.log(result);
        fs.writeFileSync(__dirname + "/users/" + user_id_str + ".json", JSON.stringify(result));
    }

    await browser.close();
})();