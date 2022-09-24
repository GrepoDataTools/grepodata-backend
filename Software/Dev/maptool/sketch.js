/*
 * @name Simple Draw
 * @description Touch to draw on the screen using mouseX, mouseY, pmouseX, and pmouseY values.
 */

let bg;

function preload(){
    bg = loadImage('bg.png');
}

function setup() {
    cnv = createCanvas(displayWidth, displayHeight);
    // background(bg, [10]);
    strokeWeight(10);
    stroke(0);

    // capture = createCapture(VIDEO);
    // capture.size(320, 240);
    // capture.hide();
}

function touchMoved() {
    line(mouseX, mouseY, pmouseX, pmouseY);
    return false;
}

// function draw() {
//     if (frameCount % 120 == 0){
//         captureFrame();
//     }
// }
// function captureFrame() {
//     // convert the graphics canvas to string, you can send that string in a POST request.
//     let imageBase64String = cnv.elt.toDataURL();
//
//     console.log(imageBase64String);
// }


function draw() {
    // background(100);
    textSize(18);
    fill(255);

    text('mouseIsPressed: ' + mouseIsPressed, 20, 20);
    text('mouseButton: ' + mouseButton, 20, 40);
    text('mouseX: ' + mouseX, 20, 60);
    text('mouseY: ' + mouseY, 20, 80);
    text('pmouseX: ' + pmouseX, 20, 100);
    text('pmouseY: ' + pmouseY, 20, 120);
    text('winMouseX: ' + winMouseX, 20, 140);
    text('winMouseY: ' + winMouseY, 20, 160);
    text('movedX: ' + movedX, 20, 180);
    text('movedY: ' + movedY, 20, 200);
    text('keyIsPressed: ' + keyIsPressed, 20, 220);
    text('key: ' + key, 20, 240);
    text('keyCode: ' + keyCode, 20, 260);
}

// function keyPressed() {
//     console.log('keyPressed: ' + key);
// }
//
// function keyReleased() {
//     console.log('keyReleased: ' + key);
// }
//
// function keyTyped(){
//     console.log('keyTyped: ' + key);
// }
//
// function mousePressed(){
//     console.log('mousePressed');
// }
//
// function mouseReleased(){
//     console.log('mouseReleased');
// }
//
// function mouseClicked(){
//     console.log('mouseClicked');
// }
//
// function mouseMoved(){
//     console.log('mouseMoved');
// }
//
// function mouseDragged(){
//     console.log('mouseDragged');
// }
//
// function mouseWheel(){
//     console.log('mouseWheel');
// }