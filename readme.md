<div align="center">
  <h3>A symfony 6 Chatbot Demo using GPT-4o, Youtube, Giphy, WeatherStack apis...🇫🇷 🇺🇸</h3>
  <p>This is a symfony 6 Chatbot Demo by using [Botman](https://botman.io). <br>
  It can be used as Chatbot symfony 6 starter.</p>
  <p>
    <a href="#">
      <img src="https://img.shields.io/badge/PRs-Welcome-brightgreen.svg?style=flat-square" alt="PRs Welcome">
    </a>
    <a href="#">
      <img src="https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square" alt="MIT License">
    </a>
  </p>
</div>

---
![screenshot1](/public/images/screenshot1.png?raw=true "chatbot 1")
![screenshot1](/public/images/screenshot2.png?raw=true "chatbot 2")

## DEMO live

[Demo deployed on Heroku](https://ai-chatbot.herokuapp.com)  

This demo is password protected. Send me a request so I can create your credentials.  
[@jessica kuijer](https://jessicakuijer.com)
## Install
```bash
composer install 
```
## Start
```bash
symfony serve -d
# open https://127.0.0.1:8000
```
On local (dev) environment, use mysql for your own use and then you can create admins with the command:
```bash
bin/console app:create:admin
```  
An invite in your terminal will ask for your credentials and password is hashed.

## Routes
You can access the main website and /login page only. (& /logout)  
Admins can access /chat page for using AI-chatbot.
## ChatBot Commands to test

- "hi" or "salut"  
- "weather in london" or "prévision météo à paris" or "météo à new york"  
- "give me a gif cats" or "envoi un gif mr bean"  
- "my name is john" or "mon nom est alice" or "je m'appelle jessica"  
- "say my name" or "dis mon nom"  
- "what's my name?" or "name" or "nom" or "quel est mon nom?"   
- "give me a youtube movie back to the future" or "donne moi une vidéo youtube chats"  
- "news trump" or "actualités pierre palmade"
- "prévision météo à paris" or "weather in new york"
- THEN... ask for everything you want, AI Claude Anthropic Sonnet 3.5 will give you answers.  🤖

## API used in POC (you have to get your own api keys as environnement variables)

[API Youtube data V3](https://developers.google.com/youtube/registering_an_application)  
[API Giphy](https://support.giphy.com/hc/en-us/articles/360020283431-Request-A-GIPHY-API-Key)  
[API Anthropic Claude bundle PHP](https://github.com/mozex/anthropic-php)  
[API WeatherStack](https://weatherstack.com/)  
[API GNews](https://gnews.io/)  

Configure your environnement variables that you can find in services.yaml parameters and .env.local   
## licenses

[MIT](./LICENSE) License © 2024  
[@vikbert for CSS and botman starter](https://vikbert.github.io)  
[@jessica kuijer](https://jessicakuijer.com)
