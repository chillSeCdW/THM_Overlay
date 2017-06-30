/**
 * checks if Overlay should be visible
 * 
 */
function checkAndToggleOverlay(id, timeStart, timeEnd) {

    var curTime = player.getCurrentTime();
    var Overlaydiv = document.getElementById(id)

    if(curTime > timeStart && curTime < timeEnd) {
            Overlaydiv.style.visibility = 'visible';
    } else {

        Overlaydiv.style.visibility = 'hidden';
    }
}