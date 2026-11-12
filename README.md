# Gazetteer

### What is it?
An application that allows the user to click/select a country on the world map. The application then moves to that country, retrieves multiple pieces of information (E.g. population, capital city name, world heritage sites etc) via API calls, then displays them. I originally created this when I learnt about the importance of APIs.

### Dependencies?
Apart from the dependencies seen in the head and bottom of the index.html file, this application also requires:
- A copy of 'Font Awesome 7 Free (for web)' in the libraries folder. The version I used at the time was 7.2.0. You can download fontawesome from here: https://fontawesome.com/download
- Your own API keys (to be put within a top level .env file) for the PHP files to use. The specific API services used are found in the PHP files.

### Status?
Fully functional as far as I'm aware. Sometimes the APIs used are discontinued, then alternatives have to be found. 

### Further Improvements?
I spent a lot of time on this application, so I'm unlikely to tinker with it further. However, further improvements would likely be in the backend. Examples are adding further resilience from broken API calls, and/or implementing more user friendly error messages in the case of failed information retrieval.

### Preview
![](media/video/GazetteerGif.gif)

### Try Here :joystick: :point_down:
https://benheaton.dev/projects/GazetteerCopy/index.html
