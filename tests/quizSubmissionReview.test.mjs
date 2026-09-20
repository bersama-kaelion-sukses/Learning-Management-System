import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../public/assets/js/courseEnrollment.js', import.meta.url), 'utf8');
// Run the actual nested quiz functions with controlled browser dependencies.
function between(start, end) {
    const from = source.indexOf(start);
    const to = source.indexOf(end, from + start.length);
    assert.ok(from >= 0 && to > from, `Missing source boundaries: ${start}`);
    return source.slice(from, to);
}
function reviewContext(extra = {}) {
    const context = vm.createContext({ AbortController, ...extra });
    vm.runInContext(between('    const hasQuizValue', '    // ====================')
        + between('            function renderQuizAttemptHistory', '            function ensureQuizContainer')
        + between('            function formatDate', '            function shuffleMultipleChoice'), context);
    return context;
}
const attempt = { mc_submission_id: 7, attempt_no: 2, grade: 33, has_answer_details: true,
    is_remedial: 0, submitted_at: '2026-09-21T12:00:00+07:00' };
const review = { ...attempt, answer_details: [
    { question_id: 1, question_text: '<script>alert(1)</script>', question_image: 'images/q.png',
        selected_option_id: 10, correct_option_id: 10, is_correct: true,
        options: [{ option_id: 10, option_text: 'A & B', option_image: 'images/a.png' }] },
    { question_id: 2, question_text: 'Wrong answer', selected_option_id: 21, correct_option_id: 20,
        is_correct: false, options: [{ option_id: 20, option_text: 'Correct' }, { option_id: 21, option_text: 'Wrong' }] },
    { question_id: 3, question_text: null, selected_option_id: null, correct_option_id: 30,
        is_correct: false, options: [{ option_id: 30, option_image: '/images/image-only.png' }] },
] };

test('review renders correct, wrong, unanswered, images and escaped content without inputs', () => {
    const html = reviewContext().renderQuizSubmissionReview(review);
    for (const text of ['Benar: 1', 'Salah: 1', 'Tidak dijawab: 1', 'Jawaban Anda', 'Jawaban benar',
        'src="/images/q.png"', 'src="/images/a.png"', 'src="/images/image-only.png"',
        '&lt;script&gt;', 'A &amp; B']) assert.ok(html.includes(text), text);
    assert.doesNotMatch(html, /<script>|<input|<form|undefined/);
    const injected = structuredClone(review);
    injected.answer_details[0].question_image = '/image.png" onerror="alert(1)';
    assert.doesNotMatch(reviewContext().renderQuizSubmissionReview(injected), /" onerror="/);
});

test('history targets exact attempt IDs and older attempts show the unavailable message', () => {
    const context = reviewContext();
    const html = context.renderQuizAttemptHistory([attempt, { ...attempt, mc_submission_id: 8, has_answer_details: false }], 70);
    assert.match(html, /data-submission-id="7"/);
    assert.doesNotMatch(html, /data-submission-id="8"/);
    assert.match(html, /Detail jawaban tidak tersedia untuk percobaan ini/);
    assert.match(context.renderQuizSubmissionReview({ has_answer_details: false }), /Detail jawaban tidak tersedia/);
});

function modalHarness(fetchResult) {
    const body = { innerHTML: '' };
    const modalEvents = {};
    const buttonEvents = {};
    let removed = false;
    let disposed = false;
    const modalEl = { setAttribute() {}, addEventListener: (name, fn) => { modalEvents[name] = fn; },
        querySelector: () => body, remove: () => { removed = true; } };
    const button = { dataset: { submissionId: '7' }, isConnected: true, focus() {},
        addEventListener: (name, fn) => { buttonEvents[name] = fn; } };
    const calls = [];
    const context = reviewContext({ itemId: 1,
        document: { createElement: () => modalEl, body: { appendChild() {} } },
        bootstrap: { Modal: class { show() {} dispose() { disposed = true; } } },
        fetch: async (...args) => { calls.push(args); return fetchResult; },
        startQuiz: () => assert.fail('Review must not start a quiz'),
        setInterval: () => assert.fail('Review must not start a timer'),
    });
    context.bindQuizReviewButtons({ querySelectorAll: () => [button] });
    return { button, body, calls, open: () => buttonEvents.click(),
        close: () => modalEvents['hidden.bs.modal'](), cleaned: () => removed && disposed };
}

test('opening and reopening review only fetches the chosen attempt; closing aborts and cleans up', async () => {
    const modal = modalHarness({ ok: true, json: async () => review });
    await modal.open();
    assert.equal(modal.calls[0][0], '/course/1/mc-submission/7');
    assert.equal(modal.calls[0][1].method, undefined); // GET
    assert.match(modal.body.innerHTML, /Percobaan #2/);
    modal.close();
    assert.ok(modal.calls[0][1].signal.aborted);
    assert.ok(modal.cleaned());
    assert.equal(modal.button.disabled, false);
    await modal.open();
    assert.equal(modal.calls.length, 2);
});

test('failed review requests show an error and allow retry after closing', async () => {
    const modal = modalHarness({ ok: false });
    await modal.open();
    assert.match(modal.body.innerHTML, /Tidak dapat memuat jawaban/);
    modal.close();
    assert.equal(modal.button.disabled, false);
});

test('timer expiry captures the current selection and sends null for unanswered questions', async () => {
    let tick;
    let payload;
    const results = [];
    const context = vm.createContext({
        window: { __QUIZ_FINISHED__: {}, __QUIZ_SUBMITTED__: {}, __ACTIVE_QUIZ__: 'instance',
            __QUIZ_DEBUG__: { 1: { submitHits: 0, instances: ['instance'] } } },
        itemId: 1, instanceId: 'instance', submissionLocked: false,
        questions: [{ question_id: 1 }, { question_id: 2 }, { question_id: 3 }],
        currentQ: 0, correctCount: 0, wrongCount: 0, answers: {}, timerInterval: null,
        quizDuration: 60, courseItemMaxAttempts: 3, storageKeyEnd: 'end', storageKeyActive: 'active',
        localStorage: { getItem: () => null, setItem() {}, removeItem() {} },
        document: {
            querySelector: selector => selector.startsWith('meta') ? { content: 'csrf' }
                : { value: '10', dataset: { correct: '1' } },
            getElementById: () => null,
        },
        countNormalAttempt: () => 0, shuffleMultipleChoice: value => value,
        renderQuestion() {}, getRemainingTime: () => 0,
        setInterval: callback => { tick = callback; return 1; }, clearInterval() {},
        renderResultDetail: (...args) => results.push(args), console,
        fetch: async (url, options) => {
            payload = JSON.parse(options.body);
            return { ok: true, json: async () => ({ success: true, attempt: 1, history: [attempt],
                data: { grade: 33, answer_details: review.answer_details } }) };
        },
    });
    vm.runInContext(between('            function autoSubmitQuiz()', '            function renderResultDetail')
        + between('            function startQuiz(', '            fetch(`/course/${itemId}/mc-submission/check`)'), context);
    context.startQuiz();
    tick();
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(payload.answer_details, [
        { question_id: 1, selected_option_id: 10 },
        { question_id: 2, selected_option_id: null },
        { question_id: 3, selected_option_id: null },
    ]);
    assert.equal(results[0][0], 33);
    assert.equal(results[0][3], 3);
});

test('post-submit results include review controls and bind them without starting another attempt', () => {
    const container = { innerHTML: '' };
    let bound = false;
    const context = reviewContext({
        ensureQuizContainer: () => container, passingGrade: 70, courseItemMaxAttempts: 1, previewMode: false,
        countNormalAttempt: attempts => attempts.length, storageKeyEnd: 'end', storageKeyActive: 'active',
        localStorage: { removeItem() {} }, document: { getElementById: () => null },
    });
    context.bindQuizReviewButtons = value => { bound = value === container; };
    vm.runInContext(between('            function renderResultDetail(', '            function renderQuestion('), context);
    context.renderResultDetail(33, 1, 1, 3, 2, [attempt]);
    assert.match(container.innerHTML, /data-submission-id="7"/);
    assert.ok(bound);
});
