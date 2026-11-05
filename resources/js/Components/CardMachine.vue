<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CardEnvelope from './CardEnvelope.vue';
import { countdownParts } from '@/cards';
import { useI18n } from '@/i18n';

const props = defineProps({
    offers: { type: Array, required: true },
    drawing: { type: Boolean, default: false },
    result: { type: Object, default: null },
    resetAt: { type: String, required: true },
});

const emit = defineEmits(['crank', 'open']);

const { t } = useI18n();
const sceneEl = ref(null);
const now = ref(new Date());
let timer = null;
let scene = null;
let initStarted = false;

const countdown = computed(() => (props.result ? countdownParts(props.resetAt, now.value) : null));

const countdownText = computed(() => {
    if (!countdown.value) {
        return '';
    }

    const { hours, minutes } = countdown.value;

    if (hours > 0 && minutes > 0) {
        return t('ui.cards_hours_minutes', { hours, minutes });
    }

    if (hours > 0) {
        return t('ui.cards_hours', { hours });
    }

    return t('ui.cards_minutes', { minutes });
});

function pull() {
    if (props.drawing || props.offers.length === 0) {
        return;
    }

    const index = Math.floor(Math.random() * props.offers.length);
    scene?.prepareDrop(index);
    emit('crank', index);
}

async function initScene() {
    if (initStarted || !sceneEl.value) {
        return;
    }

    initStarted = true;

    const probe = document.createElement('canvas');

    if (!probe.getContext('webgl')) {
        return;
    }

    const THREE = await import('three');
    const { RoundedBoxGeometry } = await import('three/addons/geometries/RoundedBoxGeometry.js');
    const { RoomEnvironment } = await import('three/addons/environments/RoomEnvironment.js');

    const container = sceneEl.value;
    const width = () => container.clientWidth || 300;
    const height = () => container.clientHeight || 450;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(width(), height());
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.domElement.className = 'card-machine__canvas';
    container.appendChild(renderer.domElement);

    const sceneRoot = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    sceneRoot.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    pmrem.dispose();

    const camera = new THREE.PerspectiveCamera(30, width() / height(), 0.1, 50);
    camera.position.set(0.42, 0.3, 7.3);
    camera.lookAt(0, -0.12, 0);

    sceneRoot.add(new THREE.AmbientLight(0xfff1d6, 0.28));

    const keyLight = new THREE.DirectionalLight(0xffe6c0, 1.5);
    keyLight.position.set(2.8, 3.8, 4.2);
    sceneRoot.add(keyLight);

    const spot = new THREE.SpotLight(0xfff2d0, 55, 0, 0.5, 0.7, 1.6);
    spot.position.set(-0.5, 4.6, 3);
    spot.target.position.set(0, 0.6, 0);
    sceneRoot.add(spot);
    sceneRoot.add(spot.target);

    const rimLight = new THREE.DirectionalLight(0xc9a96a, 0.9);
    rimLight.position.set(-3, 1.5, -3);
    sceneRoot.add(rimLight);

    const fill = new THREE.PointLight(0xd9b87c, 6, 12, 2);
    fill.position.set(-3.2, -0.8, 3);
    sceneRoot.add(fill);

    const glowCanvas = document.createElement('canvas');
    glowCanvas.width = 256;
    glowCanvas.height = 256;
    const glowCtx = glowCanvas.getContext('2d');
    const glowGradient = glowCtx.createRadialGradient(128, 128, 10, 128, 128, 126);
    glowGradient.addColorStop(0, 'rgba(232, 200, 135, 0.38)');
    glowGradient.addColorStop(1, 'rgba(232, 200, 135, 0)');
    glowCtx.fillStyle = glowGradient;
    glowCtx.fillRect(0, 0, 256, 256);

    const glowSprite = new THREE.Sprite(
        new THREE.SpriteMaterial({
            map: new THREE.CanvasTexture(glowCanvas),
            transparent: true,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
        })
    );
    glowSprite.scale.set(4.4, 5, 1);
    glowSprite.position.set(0, 0.2, -1.6);
    sceneRoot.add(glowSprite);

    const machine = new THREE.Group();
    machine.rotation.y = -0.07;
    sceneRoot.add(machine);

    const goldBody = new THREE.MeshStandardMaterial({ color: 0xb08b4e, metalness: 0.9, roughness: 0.3, envMapIntensity: 1.25 });
    const goldFrame = new THREE.MeshStandardMaterial({ color: 0xd6b878, metalness: 0.92, roughness: 0.2, envMapIntensity: 1.35 });
    const goldDark = new THREE.MeshStandardMaterial({ color: 0x8a6d3a, metalness: 0.85, roughness: 0.35, envMapIntensity: 1 });
    const bronzeDark = new THREE.MeshStandardMaterial({ color: 0x4a3820, metalness: 0.7, roughness: 0.45, envMapIntensity: 0.8 });
    const shaftMaterial = new THREE.MeshBasicMaterial({ color: 0x1a1428 });
    const pearlMaterial = new THREE.MeshPhysicalMaterial({
        color: 0xf8f1e2,
        roughness: 0.13,
        metalness: 0.04,
        clearcoat: 1,
        clearcoatRoughness: 0.1,
        envMapIntensity: 1.1,
    });

    function roundedRectShape(w, h, r, centerX = 0, centerY = 0) {
        const shape = new THREE.Shape();
        const x = centerX - w / 2;
        const y = centerY - h / 2;

        shape.moveTo(x + r, y);
        shape.lineTo(x + w - r, y);
        shape.quadraticCurveTo(x + w, y, x + w, y + r);
        shape.lineTo(x + w, y + h - r);
        shape.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        shape.lineTo(x + r, y + h);
        shape.quadraticCurveTo(x, y + h, x, y + h - r);
        shape.lineTo(x, y + r);
        shape.quadraticCurveTo(x, y, x + r, y);

        return shape;
    }

    function ringGeometry(outerW, outerH, outerR, innerW, innerH, innerR) {
        const shape = roundedRectShape(outerW, outerH, outerR);
        shape.holes.push(roundedRectShape(innerW, innerH, innerR));

        return new THREE.ExtrudeGeometry(shape, { depth: 0.07, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 2, curveSegments: 12 });
    }

    const rim = new THREE.Mesh(
        (() => {
            const shape = roundedRectShape(2.16, 3.1, 0.15);
            shape.holes.push(roundedRectShape(1.92, 2.86, 0.12));

            return new THREE.ExtrudeGeometry(shape, { depth: 0.1, bevelEnabled: true, bevelThickness: 0.015, bevelSize: 0.015, bevelSegments: 2, curveSegments: 12 });
        })(),
        goldDark
    );
    rim.position.z = -0.1;
    machine.add(rim);

    const body = new THREE.Mesh(
        (() => {
            const shape = roundedRectShape(2, 3, 0.14);
            shape.holes.push(roundedRectShape(1.0, 1.36, 0.09, -0.32, 0.28));

            return new THREE.ExtrudeGeometry(shape, { depth: 1.0, bevelEnabled: true, bevelThickness: 0.03, bevelSize: 0.03, bevelSegments: 3, curveSegments: 12 });
        })(),
        goldBody
    );
    body.position.z = -0.55;
    machine.add(body);

    const lid = new THREE.Mesh(new RoundedBoxGeometry(2.08, 0.18, 1.18, 2, 0.08), goldFrame);
    lid.position.set(0, 1.58, 0);
    machine.add(lid);

    const windowFrame = new THREE.Mesh(ringGeometry(1.16, 1.52, 0.12, 1.0, 1.36, 0.09), goldFrame);
    windowFrame.position.set(-0.32, 0.28, 0.48);
    machine.add(windowFrame);

    function interiorBackTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 384;
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 384);

        gradient.addColorStop(0, '#453d63');
        gradient.addColorStop(0.55, '#241f38');
        gradient.addColorStop(1, '#0e0c16');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 256, 384);

        const halo = ctx.createRadialGradient(128, 96, 24, 128, 150, 240);
        halo.addColorStop(0, 'rgba(255, 240, 210, 0.12)');
        halo.addColorStop(1, 'rgba(0, 0, 0, 0)');
        ctx.fillStyle = halo;
        ctx.fillRect(0, 0, 256, 384);

        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;

        return texture;
    }

    const backWall = new THREE.Mesh(
        new THREE.PlaneGeometry(1.0, 1.36),
        new THREE.MeshBasicMaterial({ map: interiorBackTexture() })
    );
    backWall.position.set(-0.32, 0.28, -0.08);
    machine.add(backWall);

    const wallTop = new THREE.Mesh(new THREE.BoxGeometry(1.06, 0.03, 0.56), shaftMaterial);
    wallTop.position.set(-0.32, 0.965, 0.16);
    machine.add(wallTop);

    const wallBottom = new THREE.Mesh(new THREE.BoxGeometry(1.06, 0.03, 0.56), shaftMaterial);
    wallBottom.position.set(-0.32, -0.405, 0.16);
    machine.add(wallBottom);

    const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(0.03, 1.42, 0.56), shaftMaterial);
    wallLeft.position.set(-0.835, 0.28, 0.16);
    machine.add(wallLeft);

    const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.03, 1.42, 0.56), shaftMaterial);
    wallRight.position.set(0.195, 0.28, 0.16);
    machine.add(wallRight);

    const interiorLight = new THREE.PointLight(0xffd9a0, 0.7, 1.9, 2);
    interiorLight.position.set(-0.32, 0.8, 0.3);
    machine.add(interiorLight);

    const glass = new THREE.Mesh(
        new THREE.PlaneGeometry(1.0, 1.36),
        new THREE.MeshPhysicalMaterial({ color: 0xffffff, transparent: true, opacity: 0.04, roughness: 0.03, metalness: 0, envMapIntensity: 0.6 })
    );
    glass.position.set(-0.32, 0.28, 0.5);
    machine.add(glass);

    function engravingTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 640;
        canvas.height = 960;
        const ctx = canvas.getContext('2d');
        const gold = 'rgba(240, 217, 160, 0.75)';
        const goldSoft = 'rgba(240, 217, 160, 0.4)';

        ctx.strokeStyle = gold;
        ctx.lineWidth = 5;
        ctx.strokeRect(26, 26, 588, 908);
        ctx.lineWidth = 2;
        ctx.strokeRect(48, 48, 544, 864);

        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(60, 120);
        ctx.quadraticCurveTo(48, 90, 84, 74);
        ctx.moveTo(580, 120);
        ctx.quadraticCurveTo(592, 90, 556, 74);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(60, 840);
        ctx.quadraticCurveTo(48, 870, 84, 886);
        ctx.moveTo(580, 840);
        ctx.quadraticCurveTo(592, 870, 556, 886);
        ctx.stroke();

        ctx.strokeStyle = goldSoft;
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.moveTo(70, 660);
        ctx.quadraticCurveTo(120, 640, 170, 662);
        ctx.moveTo(570, 660);
        ctx.quadraticCurveTo(520, 640, 470, 662);
        ctx.stroke();

        ctx.fillStyle = 'rgba(240, 217, 160, 0.85)';
        ctx.beginPath();
        ctx.moveTo(288, 780);
        ctx.quadraticCurveTo(320, 762, 320, 776);
        ctx.quadraticCurveTo(320, 762, 352, 780);
        ctx.lineTo(352, 800);
        ctx.quadraticCurveTo(320, 786, 320, 796);
        ctx.quadraticCurveTo(320, 786, 288, 800);
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = 'rgba(138, 109, 58, 0.8)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(320, 776);
        ctx.lineTo(320, 796);
        ctx.stroke();

        ctx.strokeStyle = goldSoft;
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(268, 812);
        ctx.quadraticCurveTo(240, 820, 236, 844);
        ctx.moveTo(372, 812);
        ctx.quadraticCurveTo(400, 820, 404, 844);
        ctx.stroke();

        function sparkle(cx, cy, s) {
            ctx.fillStyle = 'rgba(240, 217, 160, 0.5)';
            ctx.beginPath();
            ctx.moveTo(cx, cy - s);
            ctx.quadraticCurveTo(cx + s * 0.18, cy - s * 0.18, cx + s, cy);
            ctx.quadraticCurveTo(cx + s * 0.18, cy + s * 0.18, cx, cy + s);
            ctx.quadraticCurveTo(cx - s * 0.18, cy + s * 0.18, cx - s, cy);
            ctx.quadraticCurveTo(cx - s * 0.18, cy - s * 0.18, cx, cy - s);
            ctx.fill();
        }

        sparkle(140, 90, 12);
        sparkle(500, 90, 12);
        sparkle(320, 40, 9);
        sparkle(70, 470, 8);
        sparkle(608, 470, 8);

        ctx.clearRect(56, 170, 320, 440);

        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;

        return texture;
    }

    const decal = new THREE.Mesh(
        new THREE.PlaneGeometry(1.94, 2.9),
        new THREE.MeshBasicMaterial({ map: engravingTexture(), transparent: true, depthWrite: false })
    );
    decal.position.set(0, 0, 0.49);
    machine.add(decal);

    const column = new THREE.Mesh(new THREE.BoxGeometry(0.46, 2.0, 0.14), goldBody);
    column.position.set(0.62, 0.28, 0.48);
    machine.add(column);

    const columnTrim = new THREE.Mesh(new THREE.BoxGeometry(0.34, 1.9, 0.02), goldFrame);
    columnTrim.position.set(0.62, 0.28, 0.5);
    machine.add(columnTrim);

    const slotFrame = new THREE.Mesh(ringGeometry(1.0, 0.36, 0.1, 0.86, 0.2, 0.06), goldFrame);
    slotFrame.position.set(0, -1.16, 0.48);
    machine.add(slotFrame);

    const slotBack = new THREE.Mesh(new THREE.BoxGeometry(0.86, 0.2, 0.3), shaftMaterial);
    slotBack.position.set(0, -1.16, 0.3);
    machine.add(slotBack);

    const flapPivot = new THREE.Group();
    flapPivot.position.set(0, -1.06, 0.5);
    machine.add(flapPivot);

    const flap = new THREE.Mesh(new THREE.BoxGeometry(0.86, 0.2, 0.04), goldDark);
    flap.position.y = -0.1;
    flapPivot.add(flap);

    const footGeometry = new THREE.BoxGeometry(0.32, 0.16, 0.9);
    const footA = new THREE.Mesh(footGeometry, bronzeDark);
    footA.position.set(-0.68, -1.62, 0);
    machine.add(footA);
    const footB = new THREE.Mesh(footGeometry, bronzeDark);
    footB.position.set(0.68, -1.62, 0);
    machine.add(footB);

    const leverPivot = new THREE.Group();
    leverPivot.position.set(0.62, 0.18, 0.6);
    machine.add(leverPivot);

    const leverRod = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 0.85, 16), goldFrame);
    leverRod.position.y = 0.48;
    leverPivot.add(leverRod);

    const leverBase = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.1, 0.1), bronzeDark);
    leverBase.position.y = 0.03;
    leverPivot.add(leverBase);

    const leverKnob = new THREE.Mesh(new THREE.SphereGeometry(0.145, 24, 16), goldFrame);
    leverKnob.position.y = 0.98;
    leverPivot.add(leverKnob);

    const pearlGeometry = new THREE.SphereGeometry(1, 32, 24);
    const pearlMeshes = [];
    const pearlPositions = [
        [-0.64, -0.2, 0.2],
        [-0.32, -0.24, 0.21],
        [-0.02, -0.2, 0.2],
        [-0.52, 0.14, 0.19],
        [-0.2, 0.1, 0.2],
        [-0.4, 0.44, 0.18],
        [-0.1, 0.4, 0.17],
        [-0.28, 0.68, 0.15],
    ];

    pearlPositions.forEach(([x, y, r], index) => {
        const pearl = new THREE.Mesh(pearlGeometry, pearlMaterial);
        pearl.scale.setScalar(r);
        pearl.position.set(x, y, 0.26);
        pearl.userData = { index, baseX: x, baseY: y, baseZ: 0.26, baseR: r };
        machine.add(pearl);
        pearlMeshes.push(pearl);
    });

    const dropBall = new THREE.Mesh(pearlGeometry, pearlMaterial.clone());
    dropBall.material.transparent = true;
    dropBall.material.opacity = 1;
    dropBall.visible = false;
    machine.add(dropBall);

    const shadowCanvas = document.createElement('canvas');
    shadowCanvas.width = 256;
    shadowCanvas.height = 128;
    const shadowCtx = shadowCanvas.getContext('2d');
    const shadowGradient = shadowCtx.createRadialGradient(128, 64, 8, 128, 64, 120);
    shadowGradient.addColorStop(0, 'rgba(0, 0, 0, 0.5)');
    shadowGradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
    shadowCtx.fillStyle = shadowGradient;
    shadowCtx.fillRect(0, 0, 256, 128);
    const shadowTexture = new THREE.CanvasTexture(shadowCanvas);

    const groundShadow = new THREE.Mesh(
        new THREE.PlaneGeometry(3.1, 1.3),
        new THREE.MeshBasicMaterial({ map: shadowTexture, transparent: true, depthWrite: false })
    );
    groundShadow.rotation.x = -Math.PI / 2;
    groundShadow.position.set(0, -1.78, 0);
    sceneRoot.add(groundShadow);

    let leverPull = 0;
    let dropStart = null;
    let dropIndex = null;
    let pendingDrop = null;
    const clock = new THREE.Clock();

    function prepareDrop(index) {
        pendingDrop = index;
    }

    function startDrop(index) {
        const pearl = pearlMeshes[index % pearlMeshes.length];

        pearl.visible = false;

        dropBall.visible = true;
        dropBall.position.set(0, -1.02, 0.66);
        dropBall.scale.setScalar(0.13);
        dropBall.material.opacity = 1;

        dropStart = 0.0001;
        dropIndex = index % pearlMeshes.length;
    }

    function resetPearls() {
        dropStart = null;
        dropIndex = null;
        dropBall.visible = false;
        pearlMeshes.forEach((pearl) => {
            pearl.visible = true;
            pearl.position.set(pearl.userData.baseX, pearl.userData.baseY, pearl.userData.baseZ);
            pearl.scale.setScalar(pearl.userData.baseR);
        });
    }

    function sync(result) {
        if (result && pendingDrop !== null && dropStart === null) {
            startDrop(pendingDrop);
            pendingDrop = null;
        }

        if (!result) {
            resetPearls();
        }
    }

    function easeOutCubic(p) {
        return 1 - Math.pow(1 - p, 3);
    }

    function render() {
        const dt = Math.min(clock.getDelta(), 0.05);
        const t = clock.elapsedTime;

        if (props.drawing) {
            leverPull = Math.min(1, leverPull + dt / (reducedMotion ? 0.2 : 1.15));
        } else {
            leverPull = Math.max(0, leverPull - dt / 0.25);
        }

        const p = leverPull;

        if (p > 0) {
            const dip = p < 0.3 ? easeOutCubic(p / 0.3) : p < 0.75 ? 1 - Math.sin(((p - 0.3) / 0.45) * Math.PI) * 0.12 : 1 - easeOutCubic((p - 0.75) / 0.25);

            leverKnob.position.y = 0.98 - dip * (reducedMotion ? 0 : 0.44);
            leverRod.scale.y = 1 - dip * (reducedMotion ? 0 : 0.48);
            leverRod.position.y = 0.48 - (dip * (reducedMotion ? 0 : 0.23));
            leverKnob.position.z = Math.sin(dip * Math.PI) * (reducedMotion ? 0 : 0.1);
            leverPivot.rotation.x = reducedMotion ? 0 : Math.sin(dip * Math.PI) * 0.14;
        } else {
            leverKnob.position.y = 0.98;
            leverKnob.position.z = 0;
            leverRod.scale.y = 1;
            leverRod.position.y = 0.48;
            leverPivot.rotation.x = 0;
        }

        if (props.drawing && !reducedMotion) {
            machine.position.x = Math.sin(t * 40) * 0.007;
            machine.position.y = Math.abs(Math.sin(t * 30)) * 0.005;
        } else if (dropStart === null) {
            machine.position.set(0, 0, 0);
        }

        if (dropStart === null && !reducedMotion) {
            pearlMeshes.forEach((pearl, index) => {
                if (pearl.visible) {
                    pearl.position.y = pearl.userData.baseY + Math.sin(t * 1.3 + index * 1.3) * 0.012;
                    pearl.position.x = pearl.userData.baseX + Math.cos(t * 1.1 + index * 0.9) * 0.008;
                }
            });
        }

        if (dropStart !== null && dropIndex !== null) {
            dropStart += dt;
            const s = dropStart;

            if (s < 0.3) {
                flapPivot.rotation.x = -1.35 * easeOutCubic(s / 0.3);
            } else if (s < 0.9) {
                const f = Math.min(1, (s - 0.3) / 0.55);
                dropBall.position.y = -1.02 - f * 0.75;
                dropBall.position.z = 0.6 + f * 0.06;
                dropBall.material.opacity = f < 0.75 ? 1 : 1 - (f - 0.75) / 0.25;
            } else if (s < 1.5) {
                dropBall.visible = false;
            } else if (s < 2.1) {
                flapPivot.rotation.x = -1.35 * (1 - easeOutCubic((s - 1.5) / 0.6));
            } else {
                flapPivot.rotation.x = 0;
                dropStart = null;
                dropIndex = null;
            }
        } else if (!props.drawing) {
            flapPivot.rotation.x = 0;
        }

        renderer.render(sceneRoot, camera);
    }

    renderer.setAnimationLoop(render);

    const observer = new ResizeObserver(() => {
        renderer.setSize(width(), height());
        camera.aspect = width() / height();
        camera.updateProjectionMatrix();
    });
    observer.observe(container);

    sync(props.result);

    scene = {
        prepareDrop,
        dispose() {
            observer.disconnect();
            renderer.setAnimationLoop(null);
            sceneRoot.traverse((object) => {
                if (object.geometry) {
                    object.geometry.dispose();
                }

                if (object.material) {
                    const materials = Array.isArray(object.material) ? object.material : [object.material];
                    materials.forEach((material) => {
                        if (material.map) {
                            material.map.dispose();
                        }
                        material.dispose();
                    });
                }
            });
            renderer.dispose();
            renderer.domElement.remove();
        },
        sync,
    };
}

watch(
    () => props.result,
    (result) => {
        scene?.sync(result);
    }
);

onMounted(() => {
    timer = window.setInterval(() => {
        now.value = new Date();
    }, 60000);

    initScene();
});

onBeforeUnmount(() => {
    window.clearInterval(timer);
    scene?.dispose();
    scene = null;
    initStarted = false;
});
</script>

<template>
    <section class="card-machine mx-auto w-full max-w-md">
        <div class="card-machine__body" :class="{ 'card-machine__body--cranking': drawing }">
            <div ref="sceneEl" class="card-machine__scene" aria-hidden="true"></div>

            <div class="card-machine__window">
                <div class="card-machine__dome"></div>
            </div>

            <div class="card-machine__panel">
                <button
                    type="button"
                    class="card-machine__crank card-machine__lever"
                    :class="{ 'card-machine__crank--turning': drawing }"
                    :disabled="drawing"
                    :aria-label="t('ui.cards_crank')"
                    @click="pull"
                ></button>
            </div>

            <div class="card-machine__slot" :class="{ 'card-machine__slot--open': !!result }" aria-hidden="true"></div>
        </div>

        <div aria-live="polite" class="mt-6">
            <p v-if="!result" class="text-center text-mist">{{ t('ui.cards_choose_ball') }}</p>

            <span v-if="drawing" class="sr-only">{{ t('ui.cards_cranking') }}</span>

            <template v-if="result">
                <p v-if="!result.duplicate" class="mb-3 text-center font-serif text-lg text-gold">
                    {{ t('ui.cards_new_card') }}
                </p>
                <CardEnvelope :card="result.card" :duplicate="result.duplicate" @open="emit('open')" />
                <p v-if="countdown" class="mt-4 text-center text-sm text-mist">
                    {{ t('ui.cards_reset_in', { time: countdownText }) }}
                </p>
            </template>
        </div>
    </section>
</template>
