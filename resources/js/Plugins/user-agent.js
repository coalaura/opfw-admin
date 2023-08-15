// Copy pasted lmao
function getBrowser(nAgt) {
    let browserName = "Unknown Browser",
        verOffset, nameOffset;

    // In Opera, the true version is after "OPR" or after "Version"
    if ((verOffset = nAgt.indexOf("OPR")) != -1) {
        browserName = "Opera";
    }
    // In MS Edge, the true version is after "Edg" in userAgent
    else if ((verOffset = nAgt.indexOf("Edg")) != -1) {
        browserName = "Microsoft Edge";
    }
    // In MSIE, the true version is after "MSIE" in userAgent
    else if ((verOffset = nAgt.indexOf("MSIE")) != -1) {
        browserName = "Microsoft Internet Explorer";
    }
    // In Chrome, the true version is after "Chrome"
    else if ((verOffset = nAgt.indexOf("Chrome")) != -1) {
        browserName = "Chrome";
    }
    // In Safari, the true version is after "Safari" or after "Version"
    else if ((verOffset = nAgt.indexOf("Safari")) != -1) {
        browserName = "Safari";
    }
    // In Firefox, the true version is after "Firefox"
    else if ((verOffset = nAgt.indexOf("Firefox")) != -1) {
        browserName = "Firefox";
    }
    // In most other browsers, "name/version" is at the end of userAgent
    else if ((nameOffset = nAgt.lastIndexOf(' ') + 1) < (verOffset = nAgt.lastIndexOf('/'))) {
        browserName = nAgt.substring(nameOffset, verOffset);
    }

    return browserName;
}

function getOS(nAgt) {
    let os = "Unknown OS";

    const clientStrings = [
        { s: "Windows 10", r: /(Windows 10.0|Windows NT 10.0)/ },
        { s: "Windows 8.1", r: /(Windows 8.1|Windows NT 6.3)/ },
        { s: "Windows 8", r: /(Windows 8|Windows NT 6.2)/ },
        { s: "Windows 7", r: /(Windows 7|Windows NT 6.1)/ },
        { s: "Windows Vista", r: /Windows NT 6.0/ },
        { s: "Windows Server 2003", r: /Windows NT 5.2/ },
        { s: "Windows XP", r: /(Windows NT 5.1|Windows XP)/ },
        { s: "Windows 2000", r: /(Windows NT 5.0|Windows 2000)/ },
        { s: "Windows ME", r: /(Win 9x 4.90|Windows ME)/ },
        { s: "Windows 98", r: /(Windows 98|Win98)/ },
        { s: "Windows 95", r: /(Windows 95|Win95|Windows_95)/ },
        { s: "Windows NT 4.0", r: /(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/ },
        { s: "Windows CE", r: /Windows CE/ },
        { s: "Windows 3.11", r: /Win16/ },
        { s: "Android", r: /Android/ },
        { s: "Open BSD", r: /OpenBSD/ },
        { s: "Sun OS", r: /SunOS/ },
        { s: "Chrome OS", r: /CrOS/ },
        { s: "Linux", r: /(Linux|X11(?!.*CrOS))/ },
        { s: "iOS", r: /(iPhone|iPad|iPod)/ },
        { s: "Mac OS X", r: /Mac OS X/ },
        { s: "Mac OS", r: /(MacPPC|MacIntel|Mac_PowerPC|Macintosh)/ },
        { s: "QNX", r: /QNX/ },
        { s: "UNIX", r: /UNIX/ },
        { s: "BeOS", r: /BeOS/ },
        { s: "OS/2", r: /OS\/2/ },
        { s: "Search Bot", r: /(nuhk|Googlebot|Yammybot|Openbot|Slurp|MSNBot|Ask Jeeves\/Teoma|ia_archiver)/ }
    ];

    for (const id in clientStrings) {
        const cs = clientStrings[id];

        if (cs.r.test(nAgt)) {
            os = cs.s;

            break;
        }
    }

    return os;
}

const UserAgent = {
    install(Vue, options) {
        Vue.prototype.parseUserAgent = function (agent) {
            return {
                browser: getBrowser(agent),
                os: getOS(agent)
            };
        };
    },
}

export default UserAgent;
