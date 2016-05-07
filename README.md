# hexcrafters-handbook
Hexcrafters Handbook is a website tool for League of Legends' Hextech Crafting, developed for the 2016 Riot API Challenge.

## Demo
A live demo is the best way to understand what the site does. Check it out here at http://hexcrafter.ga

## Overview
Welcome to the Hexcrafter's Handbook!

"Hit that big juicy red button below and we'll hook you up with your Hextech Crafting stats. We'll let you know how many chests you're on track for getting by the end of the season and the best champions you should play to get those chests."

The app provides stats for summoners including:

- How many chests you've earned this season,
- The max chests available to earn this season (with a progess bar of earned chests out of max),
- How many chests you're on track to get by the end of this season, and 
- Your total champion mastery level.

The heart of the application, athough, is the champion section. Here, we analyze your stats and mastery data to detemine the best champions for you to play if you want to earn chests. We group in a couple categories: 

- Champions you have the best chance of getting a chest with by earning a "S" yourself
- Champions you have the best chance to get a chest with if you're friend in your party is likely to get the "S" for you
- Champions you've already earned chests for
- Champions that have no ranked stats or mastery data (possibly havent played)

Also, you're able to see your best mastery level with each champ.

I've found this to be a great tool for when you're in champ select and you want to chose a champ to play that you haven't earned a chest already for (because you can't look at your profile when in champ select). On top of that, you can look at which champs you've got the best shot at and filter it by position.

## Additional Features
Additional features include:
- Progress bar showing earned chests/max chests
- Tracking of available chests
- Filter your best champs to play by position (useful when you're in draft mode)
- Regional search support
- Error reporting
- Flexible updates (static champion and position data in JSON files)
- A sleek website design
- A FAQ on the website and earning chests/keys

## How it Works
We get data about your account from Riot's API and analyze it to determine the champions you have the best chance of getting an "S" and a win with. The champions are sorted based on a arbitrary score value which is the sum of 4 parts based on:

- Win percentage
- How often you get multi-kills (triple or higher)
- Your highest mastery grade (A+, S, etc.)
- Your champion mastery level (1 to 5)

To get a perfect score with a champion (which, by the way, is hidden anyhow... and its all relative) you would need to: have a win ratio 70% or higher; get a Penta kill every 6 games, a Quadra every 4 games, and a Triple 2nd game; have got an "S+" with that champion at some point; have a level 5 champion mastery. Unlikely, but keep in mind this is an upper limit for our ranking scale! The multi-kill points have generally lower impact... because otherwise Master Yi would be at the top of everyone's list! And we know you support mains would have none of that!

The "get carried" section is purely based on your win percentage.

### The math
P1 = Linear interpolation between 30% and 70% win rate to a score between 0 and 10, capped at 0 and 10

P2 = Sum of the number of multikills / games_played * difficulty_factor, capped between 0 and 3 (total of 3 points). The difficulty factors determine the number of games between when you'd get each multikill (6 games for penta kill, 4 for quadra, 2 for triple).

P3 = Linear scale with "D-" to "S+" corresponding to 0 - 14 points

P4 = Champion level (1-5) * 2


Champion Score = P1 + P2 + P3 + P4

Carry Score = 3 * P3 

### I've definitely played some of those champs...
Quite possibly! There is only so much data that is available. Your stats (wins, losses, penta kills, etc.) are only calculated for ranked games and for this season. You may have also played champions before Riot implemented the Mastery system - in which case your profile may not have any mastery data associated.

## Features Behind the Scenes
- Error catching and error reporting
- Implemented a server API (just for this website) to convienently access data, provide scalability and versioning, and keep keys and database info secure
- Using Angluar JS and Bootstrap frameworks
- Clean and documented code
- AJAX loading, single-page design, and clean URLs
- My best attempt at good-practices OOP code

## What Happens When Someone Uses the Site? (for those interested)
- Page loads and a user can choose thier region and enter their summoner name
- This info is sent via a HTTP request through Angular to the PHP-written internal API
- The API routes the request according to the endpoint (eg. "api/v1/user/goevo")
- The API then connects to the Riot API and looks up the user's info including ID
- The ID is used to make additional queries to the Riot API Stats and Champion Mastery endpoints
- Data for each champion is analyzed and a score is calculated based on the factors described previously
- Based on the information available, the champion is put in an array according to if they have a chest, played, etc.
- A JSON response is prepared and returned to the javascript code that requested it
- The data is parsed and injected into the HTML via Angular
- If an error occured (eg. the summoner does not exist), it is shown to the user instead
