## About GORA

First Time Setup
- php artisan db:seed --class=SQLSeeder


### Usage

Nearest Route API Call
```
http://localhost:8923/api/routes/nearest?origin_lat=10.275255&origin_lng=123.945516&destination_lat=10.323355&destination_lng=123.971943&radius=100
```

Payload
```
origin_lng,
origin_lat,
destination_lng,
destination_lat,
radius // In meters
```

Result
```
[
    {
        "id": "43f6ee9a-7085-4838-93bc-a2d27fe072f8",
        "name": "Cordova Mepz Via Hoopsdome",
        "points": [
            {
                "lat": 10.258199799505483,
                "lng": 123.94848346710206
            },
            ...
        ]
    },
    {
        "id": "e621e9d2-5da8-448e-8dab-d54462f846f3",
        "name": "Cordova Mepz Via Mercado",
        "points": [
            {
                "lat": 10.258199799505483,
                "lng": 123.94848346710206
            },
           ...
        ]
    }
]
```