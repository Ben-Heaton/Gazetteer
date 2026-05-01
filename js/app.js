$(document).ready(function() {

    /* ==== MAP / GeoJSON CREATION ================================================================================================================================ */

    // Map tile layers
    let streetsTileLayer = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}", {
        attribution: "Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012"
    }
    );

    let satelliteTileLayer = L.tileLayer("https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
        attribution: "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community"
    });

    // Object for map layer control button.
    let mapTileLayers = {
        "Streets": streetsTileLayer,
        "Satellite": satelliteTileLayer
    };

    // Map starts in street view.
    let map = L.map('map', {
        layers: [streetsTileLayer]
    }).setView([0.00, 0.00], 3);  // Opens initially with .setView() to show world, then if geolocation permission is true .flyToBounds()

    // Creates a new map layer and changes the default Leaflet styling...
    let geoJSONLayer = new L.geoJson(null, {
        style: {
            color: 'transparent',
            weight: '1',
            fillColor: '#F5F5F5',
            fillOpacity: 0.0,
        }
    });

    // ...and overlays it to the map.
    geoJSONLayer.addTo(map);

    // Loads the GeoJSON file data to the map.
    $.ajax({
        url: "libraries/geoJSONData/countryBorders.geo.json",
        dataType: "json",
        success: function(data) {
            $(data.features).each(function (key, data) {
                geoJSONLayer.addData(data);
            });
        },
        error: function(err) {
            console.log(err);
            //alert("GeoJSON Data Failed to Load");
        }
    });

    // Populating countrySelect with AJAX Call & PHP routine to return JSON object containing an array country names & ISO codes from countryBorders.geo.json
    $.ajax({
        url: "php/populateCountrySelectR1.php",
        dataType: "json",
        success: function(countryNamesandCodes) {

            // Appends JSON data to countrySelect.
            let select = document.getElementById('countrySelect');
            for (let country of countryNamesandCodes) {
                let option = document.createElement("option");
                option.value = country.iso_a2;
                option.textContent = country.name;
                select.appendChild(option);
            }
        },
        error: function(err) {
            console.log(err);
            $("#geoJsonErrorModal").modal("show");
            // A property and/or coords are missing from the loaded countryBorders.geo.json file. Replace it with new one.
        }
    });

    // IMPORTANT! Declared object for lookup later for exchange rate calculations.
    const currenciesData = {};

    // Populates the HTML currency converter select list with options of currencies and thier respective exchange rates (against 1 USD).
    $.ajax({
        url: "php/currentExchangeRate.php",
        dataType: "json",
        success: function(currenciesResult) {
            let select = document.getElementById('targetCurrencies');

            for (let currency of currenciesResult) {

                // This part creates options for the select list. Visiable currency names and currency code values.
                let option = document.createElement("option");
                option.value = currency.currencyCode;
                option.textContent = currency.currencyName;
                select.appendChild(option);
                
                // This part will create a lookup table for JS to use when the user interacts with the converter.
                // It'll append new key:value pairs from the JSON (since the currency codes are unique); E.g. currenciesData = {GBP: 0.14, USD: 1, etc} 
                currenciesData[currency.currencyCode] = currency.currencyValue;

            }

        },
        error: function(err) {
            console.log(err);
        }
    });

    /* ==== GLOBAL VARIABLES ====================================================================================================================================== */

    // Global variables.
    let flag = true;
	let afganistanFirst = false;
    let currentYear = null;
    let currentLatLng = [null, null];
    let userLatLng = [null, null];
    let currentAlphaTwoCodeLower = null;
    let currentAlphaTwoCodeUpper = null;
    let currentCountryName = null;
    let currentCountryCapital = null;
    let currentCountryPopulation = null;
    let currentCountryCurrencyCode = null;
    
    let currentCapitalName = null;
    let currentCapitalLatLng = [null, null];
    let previousCapitalName = null;
    let previousCapitalLatLng = [null, null];

    // Global variables for marker/cluster buttons.
    let majorCitiesCoords = [];
    let majorCitiesNames = [];
    let majorCitiesMarkers = [];
    let intAirportsCoords = [];
    let intAirportsNames = [];
    let intAirportsMarkers = [];
    let unescoSitesNames = [];
    let unescoSitesCoords = [];
    let unescoSitesMarkers = [];
    let previousLatLng = [null, null];
    let distanceKilometersAndMiles = [];
    let distanceMarkers = [];
    let distanceLine = [];

    // Flags
    let showTheDistanceFlag = false;
    let showTheSitesFlag = false;
    let showTheIntAirportsFlag = false;
    let showTheMajorCitiesFlag = false;

    // 4. The cluster groups/layers are stored here.
    let calculateDistanceLayer = L.layerGroup();
    let sitesCluster = L.markerClusterGroup();
    let airportsCluster = L.markerClusterGroup();
    let majorCitiesCluster = L.markerClusterGroup();

    // Custom Markers
    const distanceIcon = L.ExtraMarkers.icon({
        icon: 'fa-ruler-horizontal',
        markerColor: 'green',
        shape: 'circle',
        prefix: 'fa'
    });

    const unescoSiteIcon = L.ExtraMarkers.icon({
        icon: 'fa-landmark',
        markerColor: 'blue',
        shape: 'circle',
        prefix: 'fa'
    });

    const airportIcon = L.ExtraMarkers.icon({
        icon: 'fa-plane-departure',
        markerColor: 'black',
        shape: 'circle',
        prefix: 'fa'
    });

    const cityIcon = L.ExtraMarkers.icon({
        icon: 'fa-city',
        markerColor: 'orange',
        shape: 'circle',
        prefix: 'fa'
    });    

    // Used for filtering in getIntAirports(). I couldn't get it to import and use as a module.
    const internationalAirportsList = {
        AF: ['KBL', 'HEA', 'KDH', 'MZR'],
        AL: ['TIA'],
        DZ: ['ALG', 'ORN', 'CZL', 'AAE', 'TLM', 'BJA'],
        AO: ['LAD', 'VPE', 'GXG', 'SPP'],
        AG: ['ANU'],
        AR: ['EZE', 'AEP', 'COR', 'MDZ', 'BRC', 'IGR', 'ROS', 'SLA', 'USH'],
        AM: ['EVN'],
        AU: ['SYD', 'MEL', 'BNE', 'PER', 'ADL', 'CBR', 'OOL', 'CNS', 'DRW', 'HBA'],
        AT: ['VIE', 'GRZ', 'SZG', 'INN', 'LNZ'],
        AZ: ['GYD', 'NAJ'],
        BS: ['NAS', 'GGT', 'FPO'],
        BH: ['BAH'],
        BD: ['DAC', 'CGP', 'ZYL'],
        BB: ['BGI'],
        BY: ['MSQ', 'GME', 'VTB'],
        BE: ['BRU', 'CRL', 'LGG', 'OST'],
        BZ: ['BZE'],
        BJ: ['COO'],
        BT: ['PBH'],
        BO: ['VVI', 'LPB', 'CBB', 'SRE'],
        BA: ['SJJ', 'BNX'],
        BW: ['GBE', 'MUB', 'FRW'],
        BR: ['GRU', 'GIG', 'BSB', 'SSA', 'REC', 'FOR', 'POA', 'CWB', 'BEL', 'MAO', 'CGH', 'SDU'],
        BN: ['BWN'],
        BG: ['SOF', 'VAR', 'BOJ'],
        BF: ['OUA'],
        BI: ['BJM'],
        KH: ['PNH', 'REP', 'KOS'],
        CM: ['DLA', 'NSI', 'GOU'],
        CA: ['YYZ', 'YVR', 'YUL', 'YYC', 'YEG', 'YOW', 'YWG', 'YHZ', 'YQB'],
        CV: ['RAI', 'SID', 'VXE'],
        CF: ['BGF'],
        TD: ['NDJ'],
        CL: ['SCL', 'PMC', 'IQQ', 'ANF', 'CCP', 'ZAL'],
        CN: ['PEK', 'PKX', 'PVG', 'SHA', 'CAN', 'SZX', 'CTU', 'KMG', 'XIY', 'WUH', 'CSX', 'HGH', 'NKG', 'TSN', 'CKG', 'HRB', 'DLC', 'TAO', 'XMN', 'HAK'],
        CO: ['BOG', 'MDE', 'CLO', 'CTG', 'BAQ', 'ADZ'],
        KM: ['HAH'],
        CG: ['BZV', 'PNR'],
        CD: ['FIH', 'FBM', 'BKY'],
        CR: ['SJO', 'LIR'],
        CI: ['ABJ', 'BYK'],
        HR: ['ZAG', 'SPU', 'DBV', 'ZAD'],
        CU: ['HAV', 'VRA', 'SCU'],
        CY: ['LCA', 'PFO'],
        CZ: ['PRG', 'BRQ', 'OSR'],
        DK: ['CPH', 'AAL', 'AAR', 'BLL'],
        DJ: ['JIB'],
        DM: ['DOM'],
        DO: ['SDQ', 'PUJ', 'STI', 'POP'],
        TL: ['DIL'],
        EC: ['UIO', 'GYE', 'GPS'],
        EG: ['CAI', 'HRG', 'SSH', 'LXR', 'ASW', 'AUE', 'HBE'],
        SV: ['SAL'],
        GQ: ['SSG', 'BSG'],
        ER: ['ASM'],
        EE: ['TLL'],
        SZ: ['SHO'],
        ET: ['ADD', 'DIR', 'JIM'],
        FJ: ['NAN', 'SUV'],
        FI: ['HEL', 'OUL', 'TMP', 'TKU', 'RVN'],
        FR: ['CDG', 'ORY', 'NCE', 'LYS', 'MRS', 'TLS', 'BOD', 'NTE', 'SXB', 'LIL', 'BIA', 'MPL'],
        GA: ['LBV', 'POG', 'MVB'],
        GM: ['BJL'],
        GE: ['TBS', 'BUS', 'KUT'],
        DE: ['FRA', 'MUC', 'DUS', 'BER', 'HAM', 'CGN', 'STR', 'NUE', 'LEJ', 'HAJ', 'BRE'],
        GH: ['ACC', 'KMS'],
        GR: ['ATH', 'SKG', 'HER', 'RHO', 'CFU', 'JMK', 'KGS', 'ZTH', 'CHQ'],
        GD: ['GND'],
        GT: ['GUA', 'FRS'],
        GN: ['CKY'],
        GW: ['OXB'],
        GY: ['GEO'],
        HT: ['PAP', 'CAP'],
        HN: ['TGU', 'SAP', 'RTB'],
        HK: ['HKG'],
        HU: ['BUD', 'DEB'],
        IS: ['KEF', 'AEY'],
        IN: ['DEL', 'BOM', 'MAA', 'BLR', 'CCU', 'HYD', 'COK', 'AMD', 'GOI', 'PNQ', 'JAI', 'LKO', 'ATQ', 'IXC', 'GAU'],
        ID: ['CGK', 'DPS', 'SUB', 'UPG', 'PDG', 'KNO', 'BPN', 'LOP', 'MDC'],
        IR: ['IKA', 'MHD', 'TBZ', 'SYZ', 'AWZ', 'IFN'],
        IQ: ['BGW', 'BSR', 'EBL', 'NJF', 'ISU'],
        IE: ['DUB', 'ORK', 'SNN', 'KIR'],
        IL: ['TLV', 'ETH', 'VDA'],
        IT: ['FCO', 'MXP', 'VCE', 'NAP', 'CTA', 'PMO', 'BGY', 'BLQ', 'TRN', 'BRI', 'PSA', 'AHO'],
        JM: ['KIN', 'MBJ'],
        JP: ['NRT', 'HND', 'KIX', 'NGO', 'CTS', 'FUK', 'OKA', 'ITM'],
        JO: ['AMM', 'AQJ'],
        KZ: ['ALA', 'NQZ', 'CIT', 'GUW'],
        KE: ['NBO', 'MBA', 'KIS'],
        KI: ['TRW', 'CXI'],
        KP: ['FNJ'],
        KR: ['ICN', 'GMP', 'PUS', 'CJU', 'TAE'],
        KW: ['KWI'],
        KG: ['FRU', 'OSS'],
        LA: ['VTE', 'LPQ', 'PKZ'],
        LV: ['RIX'],
        LB: ['BEY'],
        LS: ['MSU'],
        LR: ['ROB', 'MLW'],
        LY: ['TIP', 'BEN', 'MJI'],
        LI: ['ZRH'],
        LT: ['VNO', 'KUN', 'PLQ'],
        LU: ['LUX'],
        MO: ['MFM'],
        MG: ['TNR', 'MJN', 'DIE'],
        MW: ['LLW', 'BLZ'],
        MY: ['KUL', 'PEN', 'BKI', 'KCH', 'LGK', 'JHB'],
        MV: ['MLE', 'GAN'],
        ML: ['BKO'],
        MT: ['MLA'],
        MH: ['MAJ'],
        MR: ['NKC', 'ATR'],
        MU: ['MRU'],
        MX: ['MEX', 'CUN', 'GDL', 'MTY', 'TIJ', 'SJD', 'PVR', 'MID', 'OAX', 'ZIH', 'ACA', 'HMO'],
        FM: ['PNI', 'TKK', 'KSA', 'YAP'],
        MD: ['KIV'],
        MC: ['MCM'],
        MN: ['ULN'],
        ME: ['TGD', 'TIV'],
        MA: ['CMN', 'RAK', 'AGA', 'FEZ', 'OUD', 'TNG'],
        MZ: ['MPM', 'BEW'],
        MM: ['RGN', 'MDL', 'NYT'],
        NA: ['WDH', 'WVB'],
        NR: ['INU'],
        NP: ['KTM', 'PKR', 'BWA'],
        NL: ['AMS', 'EIN', 'RTM', 'GRQ', 'MST'],
        NZ: ['AKL', 'CHC', 'WLG', 'ZQN', 'DUD'],
        NI: ['MGA'],
        NE: ['NIM'],
        NG: ['LOS', 'ABV', 'KAN', 'PHC', 'QLA'],
        MK: ['SKP', 'OHD'],
        NO: ['OSL', 'BGO', 'TRD', 'SVG', 'TOS', 'BOO', 'LYR'],
        OM: ['MCT', 'SLL', 'DQM'],
        PK: ['KHI', 'LHE', 'ISB', 'PEW', 'MUX', 'SKT'],
        PW: ['ROR'],
        PA: ['PTY', 'DAV'],
        PG: ['POM', 'LAE', 'RAB'],
        PY: ['ASU', 'CIO'],
        PE: ['LIM', 'CUZ', 'AQP', 'TRU', 'IQT'],
        PH: ['MNL', 'CEB', 'DVO', 'ILO', 'KLO', 'BCD', 'PPS'],
        PL: ['WAW', 'KRK', 'GDN', 'KTW', 'POZ', 'WRO', 'LCJ', 'RZE'],
        PT: ['LIS', 'OPO', 'FAO', 'FNC', 'TER', 'PDL', 'HOR'],
        QA: ['DOH'],
        RO: ['OTP', 'CLJ', 'TSR', 'IAS', 'SBZ'],
        RU: ['SVO', 'DME', 'VKO', 'LED', 'OVB', 'SVX', 'KZN', 'ROV', 'UFA', 'IKT', 'KHV', 'VVO'],
        RW: ['KGL'],
        KN: ['SKB'],
        LC: ['SLU', 'UVF'],
        VC: ['SVD'],
        WS: ['APW'],
        ST: ['TMS'],
        SA: ['RUH', 'JED', 'DMM', 'MED', 'AHB', 'TUU'],
        SN: ['DKR', 'ZIG'],
        RS: ['BEG', 'INI'],
        SC: ['SEZ', 'PRI'],
        SL: ['FNA'],
        SG: ['SIN'],
        SK: ['BTS', 'KSC'],
        SI: ['LJU', 'MBX'],
        SB: ['HIR'],
        SO: ['MGQ', 'BBO', 'KMU'],
        ZA: ['JNB', 'CPT', 'DUR', 'PLZ', 'ELS', 'GRJ'],
        SS: ['JUB', 'MAL'],
        ES: ['MAD', 'BCN', 'AGP', 'PMI', 'ALC', 'VLC', 'SVQ', 'TFS', 'TFN', 'LPA', 'IBZ', 'SDR', 'BIO'],
        LK: ['CMB', 'HRI'],
        SD: ['KRT', 'PZU'],
        SR: ['PBM'],
        SE: ['ARN', 'GOT', 'MMX', 'BMA', 'UME', 'LLA'],
        CH: ['ZRH', 'GVA', 'BSL', 'BRN'],
        SY: ['DAM', 'ALP', 'LTK'],
        TW: ['TPE', 'KHH', 'RMQ', 'TNN'],
        TJ: ['DYU', 'LBD'],
        TZ: ['DAR', 'JRO', 'ZNZ', 'MWZ'],
        TH: ['BKK', 'DMK', 'HKT', 'CNX', 'USM', 'HDY', 'UTP'],
        TG: ['LFW'],
        TO: ['TBU'],
        TT: ['POS', 'TAB'],
        TN: ['TUN', 'SFA', 'MIR', 'TOE', 'DJE'],
        TR: ['IST', 'SAW', 'ESB', 'ADB', 'AYT', 'DLM', 'BJV', 'TZX', 'GZT', 'VAN', 'KYA', 'ASR'],
        TM: ['ASB'],
        TV: ['FUN'],
        UG: ['EBB', 'GUL'],
        UA: ['KBP', 'LWO', 'ODS', 'HRK', 'DNK', 'IEV'],
        AE: ['DXB', 'AUH', 'SHJ', 'RKT', 'AAN'],
        GB: ['LHR', 'LGW', 'MAN', 'STN', 'LTN', 'EDI', 'BHX', 'GLA', 'BRS', 'LCY', 'NCL', 'LPL', 'LBA', 'ABZ', 'BFS', 'BHD', 'EXT', 'CWL', 'SOU', 'NQY'],
        US: ['ATL', 'LAX', 'ORD', 'DFW', 'DEN', 'JFK', 'SFO', 'SEA', 'LAS', 'MCO', 'EWR', 'MIA', 'PHX', 'IAH', 'BOS', 'MSP', 'DTW', 'FLL', 'PHL', 'BWI', 'SLC', 'DCA', 'SAN', 'TPA', 'PDX', 'HNL', 'MDW', 'STL', 'BNA', 'OAK', 'AUS', 'MSY', 'RDU', 'SJC', 'SMF', 'IAD', 'CLT'],
        UY: ['MVD', 'PDP'],
        UZ: ['TAS', 'SKD', 'BHK', 'NVI'],
        VU: ['VLI'],
        VE: ['CCS', 'MAR', 'VLN', 'PMV'],
        VN: ['HAN', 'SGN', 'DAD', 'CXR', 'VCA'],
        YE: ['SAH', 'ADE'],
        ZM: ['LUN', 'NLA'],
        ZW: ['HRE', 'BUQ']
    };

    // Requests the user's coordinates after a short interval so it's jolting.
    let geoRequestInterval = setTimeout(getUserLocation, 2000);

    /* ==== LISTENERS / EVENT HANDLERS ================================================================================================================================= */

    let resetMapBtn = L.easyButton("fa-rotate-right fa-xl", function (btn, map) {
        resetMap();

    }, 'Reset Map', {
        position: 'topleft'

    }).addTo(map);
    resetMapBtn.button.id = 'resetMapBtn';

    /* ---- Buttons for Modals ---------------------------------------------- */

    // 1. countryInfoModal
    let countryInfoModalBtn = L.easyButton("fa-info fa-xl", function (btn, map) {
        $("#countryInfoModal").modal("show");

    }, 'General Country Information', {
        position: 'topleft'

    }).addTo(map);
    countryInfoModalBtn.button.id = 'countryInfoModalBtn';

    // 2. currencyExchangeModal
    let currencyExchangeModalBtn = L.easyButton("fa-money-bill-transfer fa-xl", function (btn, map) {
        $("#currencyExchangeModal").modal("show");

    }, 'Currency Exchange Calculator', {
        position: 'topleft'

    }).addTo(map);
    currencyExchangeModalBtn.button.id = 'currencyExchangeModalBtn';

    // 3. weatherForecastModal
    let weatherForecastModalBtn = L.easyButton("fa-cloud-sun-rain fa-xl", function (btn, map) {
        $("#weatherForecastModal").modal("show");

    }, 'Four Day Weather Forecast', {
        position: 'topleft'

    }).addTo(map);
    weatherForecastModalBtn.button.id = 'weatherForecastModalBtn';

    // 4. countryNewsModal
    let countryNewsModalBtn = L.easyButton("fa-newspaper fa-xl", function (btn, map) {
        $("#countryNewsModal").modal("show");

    }, 'Country News', {
        position: 'topleft'

    }).addTo(map);
    countryNewsModalBtn.button.id = 'countryNewsModalBtn';

    // 5. wikipediaEntriesModal
    let wikipediaEntriesModalBtn = L.easyButton("fa-brands fa-wikipedia-w fa-xl", function (btn, map) {
        $("#wikipediaEntriesModal").modal("show");

    }, 'Country Wikipedia Entries', {
        position: 'topleft'

    }).addTo(map);
    wikipediaEntriesModalBtn.button.id = 'wikipediaEntriesModalBtn';

    // 6. currentDateTimeModal
    let currentDateTimeModalBtn = L.easyButton("fa-calendar-day fa-xl", function (btn, map) {
        $("#currentDateTimeModal").modal("show");

    }, 'Current Local Date & Time', {
        position: 'topleft'

    }).addTo(map);
    currentDateTimeModalBtn.button.id = 'currentDateTimeModalBtn';

    // 7. publicHolidaysModal
    let publicHolidaysModalBtn = L.easyButton("fa-umbrella-beach fa-xl", function (btn, map) {
        $("#publicHolidaysModal").modal("show");

    }, 'Country Public Holidays & Observances', {
        position: 'topleft'

    }).addTo(map);
    publicHolidaysModalBtn.button.id = 'publicHolidaysModalBtn';

    // 8. photosModal
    let photosModalBtn = L.easyButton("fa-images fa-xl", function (btn, map) {
        $("#photosModal").modal("show");

    }, 'Country Photos', {
        position: 'topleft'

    }).addTo(map);
    photosModalBtn.button.id = 'photosModalBtn';

    /* ---- Map Tile Layers / Checkboxes Layer Control ---------------------- */

    let overlayLayers = {
        "Show Distance to Previous Country Point/Capital": calculateDistanceLayer,
        "Show UNESCO World Heritage Sites": sitesCluster,
        "Show International Airports": airportsCluster,
        "Show Major Cities (Pop. >= 250,000)": majorCitiesCluster
    };

    // control Button (for map tiles, radio buttons). First argument are for radio buttons, then second for checkboxes.
    control = L.control.layers(mapTileLayers, overlayLayers).addTo(map);

    /* ---- Everything else ------------------------------------------------- */

    // Country Select
    $(document).on('change', '#countrySelect', function() {
		//console.log("Inside .on('change'), '#countrySelect'...");
        
		// get selected option value.
        let selectedOptionValue = $('#countrySelect').find(":selected").val();
        console.log(`countrySelect value is ${selectedOptionValue}`);
        
		// Turn the iso_a2 code into the target country's capital coordinates (E.g. GB => London LatLng) then feed into reverse geocoding.
        getCapitalCoords(selectedOptionValue, function() {
			//console.log("callback success");
            
			// Update the prior LatLng coords if needed for getCalculateDistance()
            //previousCapitalLatLng = currentCapitalLatLng;
            
            // Continue after currentCapitalLatLng has updated.
            clearPreviousCountry();
            reverseGeocoding(currentCapitalLatLng);
        });
    })

    // On click of map...
    map.on('click', onMapClick);

    // Listens for changes in the currency coverter.
    $("#baseCurrencyInt").on("change", function() {
        calculateExchangeTotal(currentCountryCurrencyCode);
    });
    
    $("#targetCurrencies").on("change", function() {
        calculateExchangeTotal(currentCountryCurrencyCode);
    });


    /* ==== FUNCTIONS ============================================================================================================================================= */

    function calculateExchangeTotal(currentCountryCurrencyCode) {
		// Values
		let baseCurrencyInt = $('#baseCurrencyInt').val();
        let baseCurrencyExchangeValue = currenciesData[currentCountryCurrencyCode];
        let targetCurrency = $('#targetCurrencies').val();
        let targetCurrencyExchangeValue = currenciesData[targetCurrency];

        // Calulation
        let exchangeTotal = (baseCurrencyInt / baseCurrencyExchangeValue) * targetCurrencyExchangeValue;
		
		// Formatting
        let formattedExchangeTotal = numeral(exchangeTotal).format('0,0.00');

        // Send the total back to modal.
        $('#exchangeResult').val(formattedExchangeTotal);
        
    }

    // Obtains the user's coordinates, first country (and subsequent info) based on permission.
    function getUserLocation() {
        navigator.geolocation.getCurrentPosition(
            // Geolocation allowed.
            function success(position) {
                //console.log("navigator allowed");
				//currentLatLng = [position.coords.latitude, position.coords.longitude];
                userLatLng = [position.coords.latitude, position.coords.longitude];
                //updatePreviousLatLng(currentLatLng[0], currentLatLng[1]);
                reverseGeocoding(userLatLng);
            },
            // Geolocation denied. Default to select optiion 'Afghanistan'.
            function error() {
				//console.log("navigator denied");
				afganistanFirst = true;
                $('#countrySelect').val("AF").change();
            }
        );
    }

    // Runs when user clicks on the map.
    function onMapClick(e) {
        clearPreviousCountry();
        currentLatLng = [e.latlng.lat, e.latlng.lng];
		
		// Send the click coords to reverse geolocation for country code click is in.
		reverseGeocodingClick(currentLatLng, function() {
            
            // Turn the iso_a2 code into the target country's capital coordinates (E.g. GB => London LatLng) then feed into reverse geocoding.
            getCapitalCoords(currentAlphaTwoCodeUpper, function() {
                    
                // Update the prior LatLng coords if needed for getCalculateDistance()
                //previousCapitalLatLng = currentCapitalLatLng;
                    
                // Continue after currentCapitalLatLng has updated.
                //clearPreviousCountry();
                reverseGeocoding(currentCapitalLatLng);
            });
        });
    }

    // Sets view to country bounds.
    function setToCountryBounds(currentAlphaTwoCodeUpper) {
        let code = currentAlphaTwoCodeUpper;
        //console.log("Inside setToCountryBounds");
		geoJSONLayer.eachLayer(function(layer) {
            if (layer.feature.properties.iso_a2 === code) {
            map.fitBounds(layer.getBounds(), { animate: false });
            }
        });
		$("#voilà").fadeOut();
    }

    // Provides smooths 'flight' to countries.
    function flyToCountryBounds(currentAlphaTwoCodeUpper) {
        let code = currentAlphaTwoCodeUpper;
        geoJSONLayer.eachLayer(function(layer) {
            if (layer.feature.properties.iso_a2 === code) {
            map.flyToBounds(layer.getBounds());
            }
        });
    }

    // Updates global variable. Used in markCalculateDistance().
    function updatePreviousLatLng(lat, lng) {
        previousLatLng = [lat, lng];
    }

    // Gets current year. Used in currentDateTime().
    function getCurrentYear() {
        let date = new Date();
        let dateYear = date.getFullYear();
        currentYear = dateYear;
    }

    // IMPORTANT!
    function getAllInfo() {
        
        /* ==== Info for Generic/Modals ==== */
        countryInfo(currentAlphaTwoCodeUpper);
        countryFlag(currentAlphaTwoCodeLower);
        currentWeather(currentCapitalLatLng);
        // weatherForecast(), LOCATED INSIDE currentWeather() due to timing issue.
        countryNews(currentAlphaTwoCodeLower);
        wikipediaEntries(currentCountryName);
        funFact(currentAlphaTwoCodeUpper);
        currentDateTime(currentCapitalLatLng);
        // publicHolidays(), LOCATED INSIDE countryInfo() due to timing issue.
        // countryPhotos(), LOCATED INSIDE countryInfo() due to timing issue.
        
        /* ==== Calls to check box AJAX functions ==== */
        getCalculateDistance(currentCapitalLatLng[0], currentCapitalLatLng[1], previousCapitalLatLng[0], previousCapitalLatLng[1]);
        getUnescoSites(currentAlphaTwoCodeUpper);
        getIntAirports(currentAlphaTwoCodeUpper);
        getMajorCities(currentAlphaTwoCodeUpper);

    }

    // Provides a 'Soft' reset. Clears variables etc that interfere with next country selected.
    function clearPreviousCountry() {
        
        geoJSONLayer.eachLayer(function(layer) {
            layer.setStyle({
                color: 'transparent',
                weight: '1',
                fillColor: '#F5F5F5',
                fillOpacity: 0.0,
            }).addTo(map);
        });
        
        clearCboxesAndMarkers();
        clearCountryInfoTables();
        document.getElementById('countryFlag').src = "media/pictures/noFlag.png";
    }

    // Ensures that markers, marker variables etc are off/cleared before next country.
    function clearCboxesAndMarkers() {
        
        // distance
        map.removeLayer(calculateDistanceLayer);
        distanceKilometersAndMiles = [];
        distanceMarkers = [];
        distanceLine = [];

        // unesco
        map.removeLayer(sitesCluster);
        sitesCluster.clearLayers();
        unescoSitesNames = [];
        unescoSitesCoords = [];
        unescoSitesMarkers = [];

        // airports
        map.removeLayer(airportsCluster);
        airportsCluster.clearLayers();
        intAirportsNames = [];
        intAirportsCoords = [];
        intAirportsMarkers = [];

        // cities
        map.removeLayer(majorCitiesCluster);
        majorCitiesCluster.clearLayers();
        majorCitiesNames = [];
        majorCitiesCoords = [];
        majorCitiesMarkers = [];

        // 'Resets' the checkboxes. methods like '.prop( "checked", false )' don't work.
        //cBoxesOverlayControl.remove();
        //cBoxesOverlayControl = L.control.layers(null, overlayLayers).addTo(map);
        //cBoxesOverlayControl.getContainer().classList.add('cBoxesBtn');
    }

    function clearCountryInfoTables() {
        document.getElementById('holidaysTableBody').innerHTML = "";
    }

    // Provides a 'Hard' reset. Clears everything.
    function resetMap() {
        
        clearPreviousCountry();
        
        // Clear global variabales.
        currentYear = null;
        previousLatLng = [null, null];
        currentLatLng = [null, null];
        currentAlphaTwoCodeLower = null;
        currentAlphaTwoCodeUpper = null;
        currentCountryName = null;
        currentCountryCapital = null;
        currentCountryPopulation = null;
        currentCountryCurrencyCode = null;

        // Change data displayed to user.
        document.getElementById('countryName').innerHTML = "---";
        document.getElementById('capital').innerHTML = "---";
        document.getElementById('population').innerHTML = "---";
        document.getElementById('currencyCode').innerHTML = "---";
        document.getElementById('baseCurrency').innerHTML = "---";
        document.getElementById('targetCurrency').innerHTML = "---";
        document.getElementById('exchangeCalc').innerHTML = "---";
        document.getElementById('funFact').innerHTML = "---";
        document.getElementById('weatherLocation').innerHTML = "---";
        document.getElementById('weatherDescription').innerHTML = "---";
        
        let countryData = document.getElementsByClassName('countryData');
        for (let element of countryData) {
            element.innerHTML = "---";
        }
        
        let wikiThumbs = document.getElementsByClassName('wikiThumbs');
        for (let element of wikiThumbs) {
            element.src = "media/pictures/placeHolderEarth100.png";
        }
        
        let allPhotos = document.getElementsByClassName('allPhotos');
        for (let element of allPhotos) {
            element.src = "media/pictures/placeHolderEarth100.png";
        }
        
        let allPhotoSources = document.getElementsByClassName('allPhotoSources');
        for (let element of allPhotoSources) {
            element.href = "";
        }
        
        document.getElementById('holidaysTableBody').innerHTML = "";
        
        let allPhotographers = document.getElementsByClassName('allPhotographers');
        for (let element of allPhotographers) {
            element.href = "";
            element.innerHTML = "---"
        }
        
        // Reset map view.
        map.flyTo([0.00, 0.00], 3);
    }

    // 3. Relevant data is passed through to the 'mark...' functions. These build the markers, layers, and clusters.
    function markCalculateDistance() {
        let kilometers = distanceKilometersAndMiles[0];
        let miles = distanceKilometersAndMiles[1];
        let distanceCoords = [currentLatLng, previousLatLng];

        let markerCurrent = L.popup().setLatLng(currentLatLng).setContent(`Distance: ${kilometers} km (${miles} miles)`);
        calculateDistanceLayer.addLayer(markerCurrent);
        
        let markerPrevious = L.marker(previousLatLng, { icon: distanceIcon });
        calculateDistanceLayer.addLayer(markerPrevious);
        
        let currentDistanceLine = L.polyline(distanceCoords, {color: 'black'});
        calculateDistanceLayer.addLayer(currentDistanceLine);
    }

    function markUnescoSites() {
        // Interate over the length of unescoSitesCoords array.
        for (let i = 0; i < unescoSitesCoords.length; i++) {
            let coords = unescoSitesCoords[i];
            // and because (in theory) both unescoSitesCoords & unescoSitesNames are the same length they keep in track.
            let name = unescoSitesNames[i];
            // Declaring a site marker.
            let siteMarker = L.marker(coords, { icon: unescoSiteIcon }).bindPopup(name);                            
            // Adding the markers into the global cluster variable.
            sitesCluster.addLayer(siteMarker);
        }
    }

    function markIntAirports() { 
        for (let i = 0; i < intAirportsNames.length; i++) {
            let airportName = intAirportsNames[i];
            let airportCoords = intAirportsCoords[i];
            let airportMarker = L.marker(airportCoords, { icon: airportIcon }).bindPopup(airportName);
            airportsCluster.addLayer(airportMarker);
        }                        
    }

    function markMajorCities() {
        for (let i = 0; i < majorCitiesNames.length; i++) {
            let cityName = majorCitiesNames[i];
            let cityCoords = majorCitiesCoords[i];
            let cityMarker = L.marker(cityCoords, { icon: cityIcon }).bindPopup(cityName);
            majorCitiesCluster.addLayer(cityMarker);
        }
    }

    /* ==== AJAX FUNCTIONS (MAP & MODALS) ======================================================================================================================== */    
    function reverseGeocoding(LatLngArr) {
        $.ajax({
            url: "php/reverseGeocoding.php",
            type: 'POST',
            dataType: "json",
            data: {
                lat: LatLngArr[0],
                lng: LatLngArr[1]
            },
            success: function (result) {
				currentAlphaTwoCodeUpper = result.countryCode;
                currentAlphaTwoCodeLower = currentAlphaTwoCodeUpper.toLowerCase();

                document.getElementById("languages").innerHTML = result.languages;

                highlightCountryAndGetInfo(currentAlphaTwoCodeUpper);
            },
            error: function (error) {
                console.log(error);
                alert("reverseGeocoding AJAX Call Error");
            }
        });
    }

    function highlightCountryAndGetInfo(currentAlphaTwoCodeUpper) {
        $.ajax({
            url: "php/getSelectedCountryFeatureR2.php",
            type: 'POST',
            dataType: "json",
            data: {
                isoA2: currentAlphaTwoCodeUpper,
            },
            success: function(result) {
                if (flag === true && afganistanFirst === true) {
					console.log("Entered highlightCountryAndGetInfo, flag === true, afganistanFirst = true");
					
                    document.getElementById('countryName').innerHTML = result.name;
                    document.getElementById('isoA2Code').innerHTML = result.iso_a2;
                    document.getElementById('isoA3Code').innerHTML = result.iso_a3;
					
					getAllInfo();
					flag = false;
					afganistanFirst = false;
					setToCountryBounds(currentAlphaTwoCodeUpper);
					
				}
                else if (flag === true && afganistanFirst === false) {
					console.log("Entered highlightCountryAndGetInfo, flag === true, afganistanFirst = false");
										
                    document.getElementById('countryName').innerHTML = result.name;
                    document.getElementById('isoA2Code').innerHTML = result.iso_a2;
                    document.getElementById('isoA3Code').innerHTML = result.iso_a3;
					
					$('#countrySelect').val(`${currentAlphaTwoCodeUpper}`);
                    
					getAllInfo();
					flag = false;
                    setToCountryBounds(currentAlphaTwoCodeUpper);

                } else if (flag === false && afganistanFirst === false) {
					console.log("Entered highlightCountryAndGetInfo, flag === false, afganistanFirst = false");
                    
					// Populate some the info.
                    document.getElementById('countryName').innerHTML = result.name;
                    document.getElementById('isoA2Code').innerHTML = result.iso_a2;
                    document.getElementById('isoA3Code').innerHTML = result.iso_a3;
					
					$('#countrySelect').val(`${currentAlphaTwoCodeUpper}`);
                    getAllInfo();

                    // Gets the country's bounds from GeoJson File data and flies to country.
                    flyToCountryBounds(currentAlphaTwoCodeUpper);
                    
                }

                // Highlights the user's country.
                geoJSONLayer.eachLayer(function(layer) {
                    if (layer.feature.properties.iso_a2 === currentAlphaTwoCodeUpper) {
                        
                        // Red: #F52E36, Blue: #446AA5, Green: #008000, Yellow: #FBF27A
                        layer.setStyle({
                            color: '#F52E36', 
                            weight: 1,
                            fillColor: '#F52E36',
                            fillOpacity: 0.2
                        });
                    }
                });
            },
            error: function(error) {
                console.log(error);
                //alert("reverseGeocoding AJAX Call Error");
            }
        });
    }

    function reverseGeocodingClick(LatLngArr, callback) {
        $.ajax({
            url: "php/reverseGeocodingClick.php",
            type: 'POST',
            dataType: "json",
            data: {
                lat: LatLngArr[0],
                lng: LatLngArr[1]
            },
            success: function (result) {
				currentAlphaTwoCodeUpper = result.countryCode;
                currentAlphaTwoCodeLower = currentAlphaTwoCodeUpper.toLowerCase();

                document.getElementById("languages").innerHTML = result.languages;

                highlightCountryAndGetInfo(currentAlphaTwoCodeUpper);

                callback();
            },
            error: function (error) {
                console.log(error);
                alert("reverseGeocoding AJAX Call Error");
            }
        });
    }

    // API call to retrieve a country's capital city coordinates.
    function getCapitalCoords(iso_a2, callback) {
        $.ajax({
            url: "php/getCapitalCoords.php",
            type: 'POST',
            data: {
                isoA2Code: iso_a2 
            },
            success: function(result) {
                currentCapitalName = result.capitalName;
                currentCapitalLatLng = [result.capitalLat, result.capitalLng];
                //currentLatLng = [capitalLat, capitalLng];
                callback();
            },
            // If response failure.
            error: function(error) {
                console.log(error);
                //alert("getCapitalCoords AJAX Call Error");
            }
        });
    }

    // API call to retrieve an image of a country's flag.
    function countryFlag(currentAlphaTwoCodeLower) {
        $.ajax({
            url: "php/countryFlag.php",
            type: 'POST',
            data: {
                alphaTwoLower: currentAlphaTwoCodeLower 
            },
            // Needed! Tells jQuery to expect binary data instead of a JSON!
            xhrFields: {
                responseType: 'blob'
            },
            success: function(blob) {
                const url = URL.createObjectURL(blob);
                document.getElementById('countryFlag').src = url;
            },
            error: function(error) {
                document.getElementById('countryFlag').src = "media/pictures/noFlag.png";
                console.log(error);
                //alert("countryFlag AJAX Call Error");
            }
        });
    }

    // API call to retrieve a country's name, capital city name, population, and currency code used.
    function countryInfo(currentAlphaTwoCodeUpper) {
        $.ajax({
            // Which file to run.
            url: "php/countryInfo.php",
            
            // Data 'transport' method used.
            type: 'POST',
            
            // Relavent data to be sent to the PHP file.
            data: {
                alphaTwoUpper: currentAlphaTwoCodeUpper 
            },
            // If response successful.
            success: function(result) {
                
                // Country Name
                currentCountryName = result.countryname;
                document.getElementById('countryName').innerHTML = currentCountryName;

                // Capital City Name                
                currentCountryCapital = result.capitalName;
                document.getElementById('capital').innerHTML = currentCountryCapital;

                // Population Size                
                currentCountryPopulation = result.population;
                document.getElementById('population').innerHTML = currentCountryPopulation;

                // Currency Code                
                currentCountryCurrencyCode = result.currencyCode;
                document.getElementById('currencyCode').innerHTML = currentCountryCurrencyCode;

                // Changing currency converter values
                document.getElementById('baseCurrency').innerHTML = currentCountryCurrencyCode;
                $('#targetCurrencies').val("USD");
				calculateExchangeTotal(currentCountryCurrencyCode);

                // LEAVE CALLS HERE. Remember AJAX calls are asynchronous! 
                countryPhotos(currentCountryName);
                publicHolidays(currentAlphaTwoCodeUpper);

            },
            // If response failure.
            error: function(error) {
                console.log(error);
                //alert("countryInfo AJAX Call Error");
            }
        });
    }

    // API call to retrieve the current weather. Specific to map clicks and coordinates. The capital's coords are used in country selection.
    function currentWeather(LatLngArr) {
        $.ajax({
            url: "php/currentWeather.php",
            type: 'POST',
            data: {
                lat: LatLngArr[0],
                lng: LatLngArr[1]
            },
            success: function(result) {
                document.getElementById('weatherCity').innerHTML = currentCountryCapital;
                document.getElementById('weatherIcon').src = `https://openweathermap.org/img/wn/${result.icon}@2x.png`;
                document.getElementById('weatherMain').innerHTML = result.main;
                document.getElementById('weatherTemp').innerHTML = result.temp;
                
                // Leave here.
                weatherForecast(currentCapitalLatLng);
            },
            error: function(error) {
                console.log(error);
                //alert("currentWeather AJAX Call Error");
            }
        });
    }

    // API call to retrieve a four day weather forecast. Specific to map clicks and coordinates. The capital's coords are used in country selection.
    function weatherForecast(LatLngArr) {
        $.ajax({
            // Which file to run.
            url: "php/weatherForecast.php",
            
            // Data 'transport' method used.
            type: 'POST',
            
            // Relavent data to be sent to the PHP file.
            data: {
                lat: LatLngArr[0],
                lng: LatLngArr[1]
            },
            // If response successful.
            success: function(result) {
                
                // Today
                document.getElementById('forecastTodayDate').innerHTML = Date.parse("today").toString("ddd, d MMM");
                document.getElementById('forecastTodayIcon').src = `https://openweathermap.org/img/wn/${result[0].icon}@2x.png`
                document.getElementById('forecastTodayMain').innerHTML = result[0].main;
                document.getElementById('forecastTodayTemp').innerHTML = result[0].temp;
                
                // Tomorrow
                document.getElementById('forecastTomorrowDate').innerHTML = Date.parse("tomorrow").toString("ddd, d MMM");
                document.getElementById('forecastTomorrowIcon').src = `https://openweathermap.org/img/wn/${result[1].icon}@2x.png`
                document.getElementById('forecastTomorrowMain').innerHTML = result[1].main;
                document.getElementById('forecastTomorrowTemp').innerHTML = result[1].temp;

                // Day 3
                document.getElementById('forecastDay3Date').innerHTML = Date.parse("t + 2d").toString("ddd, d MMM");
                document.getElementById('forecastDay3Icon').src = `https://openweathermap.org/img/wn/${result[2].icon}@2x.png`
                document.getElementById('forecastDay3Main').innerHTML = result[2].main;
                document.getElementById('forecastDay3Temp').innerHTML = result[2].temp;

                // Day 4
                document.getElementById('forecastDay4Date').innerHTML = Date.parse("t + 3d").toString("ddd, d MMM");
                document.getElementById('forecastDay4Icon').src = `https://openweathermap.org/img/wn/${result[3].icon}@2x.png`
                document.getElementById('forecastDay4Main').innerHTML = result[3].main;
                document.getElementById('forecastDay4Temp').innerHTML = result[3].temp;

                // Day 5
                document.getElementById('forecastDay5Date').innerHTML = Date.parse("t + 4d").toString("ddd, d MMM");
                document.getElementById('forecastDay5Icon').src = `https://openweathermap.org/img/wn/${result[4].icon}@2x.png`
                document.getElementById('forecastDay5Main').innerHTML = result[4].main;
                document.getElementById('forecastDay5Temp').innerHTML = result[4].temp;

            },
            // If response failure.
            error: function(error) {
                console.log(error);
                //alert("weatherForecast AJAX Call Error");
            }
        });
    }

    // API call to a country's recent news.
    function countryNews(currentAlphaTwoCodeLower) {
        $.ajax({
            url: "php/countryNews.php",
            type: 'POST',
            dataType: "json",
            data: {
                countryCode: currentAlphaTwoCodeLower
            },
            success: function(newsResult) {
                // Condition checks if the result contans an array.
                if (!Array.isArray(newsResult)) {
                    alert("Sorry, Country News cannot be loaded at this time.");

                    // Otherwise populate the news modal.
                } else {
                    for (let article of newsResult) {
                        // Establish variables and push relavent data to arrays.
                        let holidaysTableBody = document.getElementById('holidaysTableBody');
                        
                        // For every element in holsDates (thus holsNames & holsDescs) add table rows and cells.
                        for (let i = 0; i < result.holDates.length; i++) {
                            let inNewRow = holidaysTableBody.insertRow();
                            inNewRow.insertCell(0).textContent = Date.parse(result.holDates[i]).toString("dd-MM-yyyy");
                            inNewRow.insertCell(1).textContent = result.holNames[i];
                            inNewRow.insertCell(2).textContent = result.holDescs[i];
                        }
                    }
                }
            },
            error: function(error) {
                console.log(error);
                //alert("funFact AJAX Call Error");
            }
        });
    }

    // API call to retrieve wikipedia articles. Specific to map clicks and coordinates. The capital's coords are used in country selection.
    function wikipediaEntries(countryName) {
        $.ajax({
            url: "php/wikipediaEntries.php",
            type: 'POST',
            dataType: "json",   // jQuery does not automatically parse JSON unless you tell it to.
            data: {
                country: countryName
            },
            success: function(result) {
                let wikipediaBaseURL = "https://en.wikipedia.org/wiki/";
                
                document.getElementById('wikiThumb_1').src = result.thumbnail[0];
                document.getElementById('wikiLink_1').innerHTML = result.title[0];
                document.getElementById('wikiLink_1').href = wikipediaBaseURL + `${result.title[0]}`;

                document.getElementById('wikiThumb_2').src = result.thumbnail[1];
                document.getElementById('wikiLink_2').innerHTML = result.title[1];
                document.getElementById('wikiLink_2').href = wikipediaBaseURL + `${result.title[1]}`;

                document.getElementById('wikiThumb_3').src = result.thumbnail[2];
                document.getElementById('wikiLink_3').innerHTML = result.title[2];
                document.getElementById('wikiLink_3').href = wikipediaBaseURL + `${result.title[2]}`;

                document.getElementById('wikiThumb_4').src = result.thumbnail[3];
                document.getElementById('wikiLink_4').innerHTML = result.title[3];
                document.getElementById('wikiLink_4').href = wikipediaBaseURL + `${result.title[3]}`;

                document.getElementById('wikiThumb_5').src = result.thumbnail[4];
                document.getElementById('wikiLink_5').innerHTML = result.title[4];
                document.getElementById('wikiLink_5').href = wikipediaBaseURL + `${result.title[4]}`;

                document.getElementById('wikiThumb_6').src = result.thumbnail[5];
                document.getElementById('wikiLink_6').innerHTML = result.title[5];
                document.getElementById('wikiLink_6').href = wikipediaBaseURL + `${result.title[5]}`;

                document.getElementById('wikiThumb_7').src = result.thumbnail[6];
                document.getElementById('wikiLink_7').innerHTML = result.title[6];
                document.getElementById('wikiLink_7').href = wikipediaBaseURL + `${result.title[6]}`;

                document.getElementById('wikiThumb_8').src = result.thumbnail[7];
                document.getElementById('wikiLink_8').innerHTML = result.title[7];
                document.getElementById('wikiLink_8').href = wikipediaBaseURL + `${result.title[7]}`;

                document.getElementById('wikiThumb_9').src = result.thumbnail[8];
                document.getElementById('wikiLink_9').innerHTML = result.title[8];
                document.getElementById('wikiLink_9').href = wikipediaBaseURL + `${result.title[8]}`;

                document.getElementById('wikiThumb_10').src = result.thumbnail[9];
                document.getElementById('wikiLink_10').innerHTML = result.title[9];
                document.getElementById('wikiLink_10').href = wikipediaBaseURL + `${result.title[9]}`;

                /*
                //Gets rid of any rows that don't contain information etc.
                if (titleURLs.length < 10) {
                    for (let i = titleURLs.length + 1; i <= 10; i++) {
                        document.getElementById('wiki_' + i).style.display = 'none';
                    }
                }
                */
            },
            error: function(error) {
                console.log(error);
                //alert("wikipediaEntries AJAX Call Error");
            }
        });
    }

    // API call to retrieve a fun fact about the country. API is not extensive hence else statement.
    function funFact(currentAlphaTwoCodeUpper) {
        $.ajax({
            url: "php/funFact.php",
            type: 'POST',
            dataType: "json",
            data: {
                countryCode: currentAlphaTwoCodeUpper 
            },
            success: function(result) {
                document.getElementById('funFact').innerHTML = result.funFact;
            },
            error: function(error) {
                document.getElementById('funFact').innerHTML = "Sorry. No fun fact is available!";
                console.log(error);
                //alert("funFact AJAX Call Error");
            }
        });
    }

    // API call to retrieve the current date and time in the country as of API call.
    function currentDateTime(LatLngArr) {
        $.ajax({
            url: "php/currentDateTime.php",
            type: 'POST',
            dataType: "json",
            data: {
                lat: LatLngArr[0],
                lng: LatLngArr[1]
            },
            success: function(result) {
                document.getElementById('dateTime_day').innerHTML = result.day;
                document.getElementById('dateTime_month').innerHTML = result.month;
                document.getElementById('dateTime_year').innerHTML = result.year;
                document.getElementById('dateTime_dotw').innerHTML = result.dotw;
                document.getElementById('dateTime_time').innerHTML = result.time;
            },
            error: function(error) {
                console.log(error);
                //alert("currentDateTime AJAX Call Error");
            }
        });
    }

    // API call to retrieve the holidays/observances of a country.
    function publicHolidays(currentAlphaTwoCodeUpper) {       
        
        // Calls function to get current year, E.g. 2026
        getCurrentYear();
        
        $.ajax({
            url: "php/publicHolidays.php",
            type: 'POST',
            dataType: "json",
            data: {
                countryCode: currentAlphaTwoCodeUpper,
                year: currentYear
            },
            success: function(result) {
                
                // Establish variables and push relavent data to arrays.
                let holidaysTableBody = document.getElementById('holidaysTableBody');
                
                // For every element in holsDates (thus holsNames & holsDescs) add table rows and cells.
                for (let i = 0; i < result.holDates.length; i++) {
                    let inNewRow = holidaysTableBody.insertRow();
                    inNewRow.insertCell(0).textContent = Date.parse(result.holDates[i]).toString("dd-MM-yyyy");
                    inNewRow.insertCell(1).textContent = result.holNames[i];
                    inNewRow.insertCell(2).textContent = result.holDescs[i];
                }
            },
            error: function(error) {
                // Show error modal if gone wrong
                console.log(error);
                //alert("publicHolidays AJAX Call Error");
            }
        });

    }

    // API call to retrieve photos of the country.
    function countryPhotos(currentCountryName) {
        
        $.ajax({
            url: "php/countryPhotos.php",
            type: 'POST',
            dataType: "json",
            data: {
                countryName: currentCountryName,
            },
            success: function(result) {
                
                // Photo 1
                document.getElementById('photo_1').src = result[0].photoMedium;
                document.getElementById('photo_1').alt = result[0].photoAlt;
                document.getElementById('photoSource_1').href = result[0].photoUrl;
                document.getElementById('photographer_1').href = result[0].photographerUrl;
                document.getElementById('photographer_1').innerHTML = result[0].photographerName;

                // Photo 2
                document.getElementById('photo_2').src = result[1].photoMedium;
                document.getElementById('photo_2').alt = result[1].photoAlt;
                document.getElementById('photoSource_2').href = result[1].photoUrl;
                document.getElementById('photographer_2').href = result[1].photographerUrl;
                document.getElementById('photographer_2').innerHTML = result[1].photographerName;

                // Photo 3
                document.getElementById('photo_3').src = result[2].photoMedium;
                document.getElementById('photo_3').alt = result[2].photoAlt;
                document.getElementById('photoSource_3').href = result[2].photoUrl;
                document.getElementById('photographer_3').href = result[2].photographerUrl;
                document.getElementById('photographer_3').innerHTML = result[2].photographerName;

                // Photo 4
                document.getElementById('photo_4').src = result[3].photoMedium;
                document.getElementById('photo_4').alt = result[3].photoAlt;
                document.getElementById('photoSource_4').href = result[3].photoUrl;
                document.getElementById('photographer_4').href = result[3].photographerUrl;
                document.getElementById('photographer_4').innerHTML = result[3].photographerName;

                // Photo 5
                document.getElementById('photo_5').src = result[4].photoMedium;
                document.getElementById('photo_5').alt = result[4].photoAlt;
                document.getElementById('photoSource_5').href = result[4].photoUrl;
                document.getElementById('photographer_5').href = result[4].photographerUrl;
                document.getElementById('photographer_5').innerHTML = result[4].photographerName;

                // Photo 6
                document.getElementById('photo_6').src = result[5].photoMedium;
                document.getElementById('photo_6').alt = result[5].photoAlt;
                document.getElementById('photoSource_6').href = result[5].photoUrl;
                document.getElementById('photographer_6').href = result[5].photographerUrl;
                document.getElementById('photographer_6').innerHTML = result[5].photographerName;

                // Photo 7
                document.getElementById('photo_7').src = result[6].photoMedium;
                document.getElementById('photo_7').alt = result[6].photoAlt;
                document.getElementById('photoSource_7').href = result[6].photoUrl;
                document.getElementById('photographer_7').href = result[6].photographerUrl;
                document.getElementById('photographer_7').innerHTML = result[6].photographerName;

                // Photo 8
                document.getElementById('photo_8').src = result[7].photoMedium;
                document.getElementById('photo_8').alt = result[7].photoAlt;
                document.getElementById('photoSource_8').href = result[7].photoUrl;
                document.getElementById('photographer_8').href = result[7].photographerUrl;
                document.getElementById('photographer_8').innerHTML = result[7].photographerName;

                // Photo 9
                document.getElementById('photo_9').src = result[8].photoMedium;
                document.getElementById('photo_9').alt = result[8].photoAlt;
                document.getElementById('photoSource_9').href = result[8].photoUrl;
                document.getElementById('photographer_9').href = result[8].photographerUrl;
                document.getElementById('photographer_9').innerHTML = result[8].photographerName;

                // Photo 10
                document.getElementById('photo_10').src = result[9].photoMedium;
                document.getElementById('photo_10').alt = result[9].photoAlt;
                document.getElementById('photoSource_10').href = result[9].photoUrl;
                document.getElementById('photographer_10').href = result[9].photographerUrl;
                document.getElementById('photographer_10').innerHTML = result[9].photographerName;

                // Photo 11
                document.getElementById('photo_11').src = result[10].photoMedium;
                document.getElementById('photo_11').alt = result[10].photoAlt;
                document.getElementById('photoSource_11').href = result[10].photoUrl;
                document.getElementById('photographer_11').href = result[10].photographerUrl;
                document.getElementById('photographer_11').innerHTML = result[10].photographerName;

                // Photo 12
                document.getElementById('photo_12').src = result[11].photoMedium;
                document.getElementById('photo_12').alt = result[11].photoAlt;
                document.getElementById('photoSource_12').href = result[11].photoUrl;
                document.getElementById('photographer_12').href = result[11].photographerUrl;
                document.getElementById('photographer_12').innerHTML = result[11].photographerName;

                // Photo 13
                document.getElementById('photo_13').src = result[12].photoMedium;
                document.getElementById('photo_13').alt = result[12].photoAlt;
                document.getElementById('photoSource_13').href = result[12].photoUrl;
                document.getElementById('photographer_13').href = result[12].photographerUrl;
                document.getElementById('photographer_13').innerHTML = result[12].photographerName;

                // Photo 14
                document.getElementById('photo_14').src = result[13].photoMedium;
                document.getElementById('photo_14').alt = result[13].photoAlt;
                document.getElementById('photoSource_14').href = result[13].photoUrl;
                document.getElementById('photographer_14').href = result[13].photographerUrl;
                document.getElementById('photographer_14').innerHTML = result[13].photographerName;

                // Photo 15
                document.getElementById('photo_15').src = result[14].photoMedium;
                document.getElementById('photo_15').alt = result[14].photoAlt;
                document.getElementById('photoSource_15').href = result[14].photoUrl;
                document.getElementById('photographer_15').href = result[14].photographerUrl;
                document.getElementById('photographer_15').innerHTML = result[14].photographerName;

                // Photo 16
                document.getElementById('photo_16').src = result[15].photoMedium;
                document.getElementById('photo_16').alt = result[15].photoAlt;
                document.getElementById('photoSource_16').href = result[15].photoUrl;
                document.getElementById('photographer_16').href = result[15].photographerUrl;
                document.getElementById('photographer_16').innerHTML = result[15].photographerName;

                // Photo 17
                document.getElementById('photo_17').src = result[16].photoMedium;
                document.getElementById('photo_17').alt = result[16].photoAlt;
                document.getElementById('photoSource_17').href = result[16].photoUrl;
                document.getElementById('photographer_17').href = result[16].photographerUrl;
                document.getElementById('photographer_17').innerHTML = result[16].photographerName;

                // Photo 18
                document.getElementById('photo_18').src = result[17].photoMedium;
                document.getElementById('photo_18').alt = result[17].photoAlt;
                document.getElementById('photoSource_18').href = result[17].photoUrl;
                document.getElementById('photographer_18').href = result[17].photographerUrl;
                document.getElementById('photographer_18').innerHTML = result[17].photographerName;

                // Photo 19
                document.getElementById('photo_19').src = result[18].photoMedium;
                document.getElementById('photo_19').alt = result[18].photoAlt;
                document.getElementById('photoSource_19').href = result[18].photoUrl;
                document.getElementById('photographer_19').href = result[18].photographerUrl;
                document.getElementById('photographer_19').innerHTML = result[18].photographerName;

                // Photo 20
                document.getElementById('photo_20').src = result[19].photoMedium;
                document.getElementById('photo_20').alt = result[19].photoAlt;
                document.getElementById('photoSource_20').href = result[19].photoUrl;
                document.getElementById('photographer_20').href = result[19].photographerUrl;
                document.getElementById('photographer_20').innerHTML = result[19].photographerName;

            },
            error: function(error) {
                // Show error modal
                console.log(error);
                //alert("countryPhotos AJAX Call Error");
            }
        });

    }

    /* ==== AJAX FUNCTIONS (CHECKBOXES) ================================================================================================================== */
    // 2. AJAX does the API calls and receive JSONs. 

    // API call to calculate the distance between the current country location and the users last country selection.
    function getCalculateDistance(currentCapitalLat, currentCapitalLng, previousCapitalLat, previousCapitalLng) {
        $.ajax({
            url: "php/calculateDistance.php",
            type: 'POST',
            data: {
                ccLat: currentCapitalLat,
                ccLng: currentCapitalLng,
                pcLat: previousCapitalLat,
                pcLng: previousCapitalLng
            },
            success: function(result) {
                let resultKilometers = result.kilometers;
                let kilometers2SF = resultKilometers.toFixed(2);
                distanceKilometersAndMiles.push(kilometers2SF);

                let resultMiles = result.miles;
                let miles2SF = resultMiles.toFixed(2);
                distanceKilometersAndMiles.push(miles2SF);
                
                markCalculateDistance();
            },
            error: function(error) {
                console.log(error);
                //alert("getCalculateDistance AJAX Call Error");
            }
        });
    }

    // API call to retrieve a country's UNESCO world heritage sites.
    function getUnescoSites(currentAlphaTwoCodeUpper) {
                
        $.ajax({
            url: "php/unescoSites.php",
            type: 'POST',
            dataType: "json",
            data: {
                alphaTwoUpper: currentAlphaTwoCodeUpper 
            },
            success: function(unescoResult) {
                // Condition checks if the result contans an array.
                if (!Array.isArray(unescoResult)) {
                    alert("Sorry, UNESCO sites cannot be loaded at this time.");

                    // Otherwise push the sites & do function.
                } else {
                    for (let site of unescoResult) {
                        unescoSitesNames.push(site.siteName);
                        unescoSitesCoords.push([site.siteLat, site.siteLng]);
                    }
                    markUnescoSites();
                }
            },
            error: function(error) {
                console.log(error);
                //alert("getUnescoSites AJAX Call Error");
            }
        });
    }

    // API call to retrieve a country's international airports.
    function getIntAirports(currentAlphaTwoCodeUpper) {
        
        // Used for filtering later. 
        let countryKnownIntAirports = internationalAirportsList[`${currentAlphaTwoCodeUpper}`];
        
        $.ajax({
            url: "php/internationalAirports.php",
            type: 'POST',
            dataType: "json",
            data: {
                alphaTwoUpper: currentAlphaTwoCodeUpper 
            },
            success: function(airportsResult) {
                
                // Check if the result is usable for the evental markers.
                if (!Array.isArray(airportsResult)) {
                    alert("Sorry, airports cannot be loaded at this time.");

                } else {
                    // Sorting through the JSON for actual major international airports by comparing it against my JS internationalAirportsList object.
                    for (let airport of airportsResult) {
                        
                        // First sort.
                        if (airport.iata_code !== null && airport.icao_code !== null) {
                            
                            // Second sort. Compare with imported object.
                            if (countryKnownIntAirports.includes(airport.iata_code)) {
                                
                                // Push the coords and names to thier global arrays.
                                intAirportsCoords.push([airport.airportLat, airport.airportLng]);
                                intAirportsNames.push(airport.airportName);
                            }
                        }
                    };
                    markIntAirports();
                }
            },
            error: function(error) {
                console.log(error);
                //alert("getIntAirports AJAX Call Error");
            }
        });
    }

    // API call to retrieve a country's most populated cites. Data credited to 'GeoDB Cities' and 'rapidapi.com'.
    function getMajorCities(currentAlphaTwoCodeUpper) {
        $.ajax({
            url: "php/majorCities.php",
            type: 'POST',
            dataType: "json",
            data: {
                alphaTwoUpper: currentAlphaTwoCodeUpper 
            },
            success: function(result) {
                for (let city of result) {
                    majorCitiesCoords.push([city.cityLat, city.cityLng]);
                    majorCitiesNames.push(city.cityName);
                }
                markMajorCities();
            },
            error: function(error) {
                // Say this button thing doesn't work
                console.log(error);
                //alert("getMajorCities AJAX Call Error");
            }
        });
    }   

}); // End of .ready()