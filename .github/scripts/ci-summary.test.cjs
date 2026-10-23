'use strict';

const assert = require('node:assert/strict');
const { test } = require('node:test');
const writeSummary = require('./ci-summary.cjs');

async function renderSummary(jobs, result = 'success') {
  const output = {};
  const endpoint = Symbol('listJobsForWorkflowRun');
  const github = {
    rest: { actions: { listJobsForWorkflowRun: endpoint } },
    async paginate(method, options) {
      assert.equal(method, endpoint);
      assert.deepEqual(options, {
        owner: 'imbo',
        repo: 'behat-api-extension',
        run_id: 123,
        filter: 'latest',
        per_page: 100,
      });
      return jobs;
    },
  };
  const summary = {
    addHeading(value, level) {
      output.heading = { value, level };
      return this;
    },
    addRaw(value) {
      output.overall = value;
      return this;
    },
    addTable(value) {
      output.table = value;
      return this;
    },
    async write() {
      output.written = true;
    },
  };

  await writeSummary({
    github,
    context: { repo: { owner: 'imbo', repo: 'behat-api-extension' }, runId: 123 },
    core: { summary },
    result,
  });

  return output;
}

test('includes new matrix entries, sorts checks, and excludes the summary job', async () => {
  const output = await renderSummary([
    { name: 'Branch protection', status: 'in_progress' },
    {
      name: 'composer run new-check / PHP 8.6 (new-runner)',
      conclusion: 'success',
      started_at: '2026-10-23T10:00:00Z',
      completed_at: '2026-10-23T10:01:05Z',
      html_url: 'https://example.com/job/1',
    },
    {
      name: 'composer run behat / PHP 8.3 (ubuntu-26.04)',
      conclusion: 'failure',
      html_url: 'https://example.com/job/2',
    },
  ]);

  assert.deepEqual(output.heading, { value: 'CI summary', level: 2 });
  assert.deepEqual(output.table[0],
    ['Check / PHP / Runner', 'Result', 'Duration', 'Logs'].map(data => ({ data, header: true })));
  assert.equal(output.table.length, 3);
  assert.equal(output.table[1][0], 'composer run behat / PHP 8.3 (ubuntu-26.04)');
  assert.deepEqual(output.table[2], [
    'composer run new-check / PHP 8.6 (new-runner)',
    'success',
    '1m 5s',
    '<a href="https://example.com/job/1">View logs</a>',
  ]);
  assert.equal(output.written, true);
});

for (const result of ['success', 'failure', 'cancelled', 'skipped']) {
  test(`reports the overall and individual ${result} result`, async () => {
    const output = await renderSummary([
      { name: 'composer run phpunit / PHP 8.5 (windows-latest)', conclusion: result },
    ], result);

    assert.equal(output.overall, `Overall result: ${result}`);
    assert.equal(output.table[1][1], result);
    assert.equal(output.table[1][2], '—');
  });
}

test('falls back to job status when there is no conclusion', async () => {
  const output = await renderSummary([
    { name: 'composer run cs / PHP 8.5 (ubuntu-26.04)', conclusion: null, status: 'queued' },
  ]);

  assert.equal(output.table[1][1], 'queued');
  assert.equal(output.table[1][2], '—');
});

test('writes a summary when there are no matrix jobs', async () => {
  const output = await renderSummary([], 'skipped');

  assert.equal(output.table.length, 1);
  assert.equal(output.written, true);
});
