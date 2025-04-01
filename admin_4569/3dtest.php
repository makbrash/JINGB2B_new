<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Configuratore Mattoni in Legno (ES Modules)</title>

  <!-- jQuery (CDN) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      margin: 0;
      overflow: hidden;
      font-family: sans-serif;
    }
    #canvasContainer {
      width: 100vw;
      height: 100vh;
      display: block;
      position: relative;
    }
    #toolbar {
      position: absolute;
      top: 10px;
      left: 10px;
      padding: 10px;
      background: rgba(255,255,255,0.7);
      border-radius: 4px;
    }
    button {
      margin: 5px;
    }
  </style>
</head>
<body>

<div id="canvasContainer">
  <div id="toolbar">
    <button id="addMattonex">Aggiungi Mattone X2Y1Z2</button>
    <button id="addMattoney">Aggiungi Mattone X1Y2Z2</button>
    <button id="deleteSelected">Elimina Selezionato</button>
    <button id="rotateSelected">Ruota Selezionato</button>
  </div>
</div>

<!-- Tutto il codice Three.js e OrbitControls è importato via ES Modules -->
<script type="module">
  import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.151.3/build/three.module.js';
  import { OrbitControls } from 'https://cdn.jsdelivr.net/npm/three@0.151.3/examples/jsm/controls/OrbitControls.js';


  let scene, camera, renderer, orbitControls;
  let raycaster, mouse;
  let selectedObject = null;
  let bricks = [];

  // Avvio
  init();
  animate();

  function init() {
    const container = document.getElementById('canvasContainer');

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf0f0f0);

    camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.set(10, 15, 25);

    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    // OrbitControls (importato come classe)
    orbitControls = new OrbitControls(camera, renderer.domElement);
    orbitControls.enableDamping = true;
    orbitControls.dampingFactor = 0.05;

    // Luci
    const light = new THREE.DirectionalLight(0xffffff, 1);
    light.position.set(10, 20, 10);
    scene.add(light);

    const ambient = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambient);

    // Raycaster + mouse
    raycaster = new THREE.Raycaster();
    mouse = new THREE.Vector2();

    // Piano
    const planeGeometry = new THREE.PlaneGeometry(50, 50);
    const planeMaterial = new THREE.MeshLambertMaterial({ color: 0xcccccc, side: THREE.DoubleSide });
    const ground = new THREE.Mesh(planeGeometry, planeMaterial);
    ground.rotation.x = - Math.PI / 2;
    scene.add(ground);

    // Eventi mouse
    renderer.domElement.addEventListener('mousedown', onMouseDown, false);
    renderer.domElement.addEventListener('mousemove', onMouseMove, false);

    // Eventi bottoni (usando jQuery che hai già importato in head)
    $('#addMattonex').click(() => addBrick({ x:2, y:1, z:2 }));
    $('#addMattoney').click(() => addBrick({ x:1, y:2, z:2 }));
    $('#deleteSelected').click(() => {
      if(selectedObject){
        scene.remove(selectedObject);
        bricks = bricks.filter(obj => obj !== selectedObject);
        selectedObject = null;
      }
    });
    $('#rotateSelected').click(() => {
      if(selectedObject){
        selectedObject.rotation.y += Math.PI / 2;
      }
    });

    // Ridimensionamento finestra
    window.addEventListener('resize', onWindowResize, false);
  }

  function addBrick(dim) {
    const geometry = new THREE.BoxGeometry(dim.x, dim.y, dim.z);
    const material = new THREE.MeshLambertMaterial({ color: 0x8B4513 }); 
    const brick = new THREE.Mesh(geometry, material);

    brick.position.set(0, dim.y/2, 0);
    scene.add(brick);
    bricks.push(brick);
  }

  let isDragging = false;
  let offset = new THREE.Vector3();
  let plane = new THREE.Plane();

  function onMouseDown(event) {
    event.preventDefault();
    let rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObjects(bricks);

    if(intersects.length > 0) {
      selectedObject = intersects[0].object;
      bricks.forEach(b => {
        b.material.emissive = new THREE.Color(0x000000);
      });
      selectedObject.material.emissive = new THREE.Color(0x444444);

      isDragging = true;

      plane.setFromNormalAndCoplanarPoint(
        camera.getWorldDirection(new THREE.Vector3()),
        selectedObject.position
      );

      if(intersects[0].point) {
        offset.copy(intersects[0].point).sub(selectedObject.position);
      }
    } else {
      selectedObject = null;
      bricks.forEach(b => {
        b.material.emissive = new THREE.Color(0x000000);
      });
    }
  }

  function onMouseMove(event) {
    event.preventDefault();
    if(isDragging && selectedObject) {
      let rect = renderer.domElement.getBoundingClientRect();
      mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
      mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

      raycaster.setFromCamera(mouse, camera);
      const planeIntersect = raycaster.ray.intersectPlane(plane, new THREE.Vector3());
      if(planeIntersect) {
        selectedObject.position.copy(planeIntersect.sub(offset));
        selectedObject.position.x = Math.round(selectedObject.position.x);
        selectedObject.position.z = Math.round(selectedObject.position.z);

        if(checkCollision(selectedObject)) {
          selectedObject.position.x += 0.5;
          selectedObject.position.z += 0.5;
        }
      }
    }
  }

  window.addEventListener('mouseup', () => {
    isDragging = false;
  }, false);

  function checkCollision(movingBrick) {
    let movingBB = new THREE.Box3().setFromObject(movingBrick);
    for(let b of bricks) {
      if(b === movingBrick) continue;
      let otherBB = new THREE.Box3().setFromObject(b);
      if(movingBB.intersectsBox(otherBB)) {
        return true;
      }
    }
    return false;
  }

  function animate() {
    requestAnimationFrame(animate);
    orbitControls.update();
    renderer.render(scene, camera);
  }

  function onWindowResize(){
    const container = document.getElementById('canvasContainer');
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  }

</script>
</body>
</html>
