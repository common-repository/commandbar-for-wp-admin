const getOperatingSystem = function() {
    const userAgent = window.navigator.userAgent;
    const platform = window.navigator.platform;
    const macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'];
    const windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'];
    const iosPlatforms = ['iPhone', 'iPad', 'iPod'];

    // default to Mac
    let os = 'mac';

    if (macosPlatforms.indexOf(platform) !== -1) {
        os = 'mac';
    } else if (iosPlatforms.indexOf(platform) !== -1) {
        os = 'ios';
    } else if (windowsPlatforms.indexOf(platform) !== -1) {
        os = 'windows';
    } else if (/Android/.test(userAgent)) {
        os = 'android';
    } else if (/Linux/.test(platform)) {
        os = 'linux';
    }

    return os;
};

const osControlKey = function(suffix) {
    if (['windows', 'android', 'linux'].includes(getOperatingSystem())) {
        if (!!suffix) {
        return `Ctrl-${suffix}`;
        }
        return 'Ctrl';
    } else {
        if (!!suffix) {
          return `⌘${suffix}`;
        }
        return '⌘';
    }
};