'use strict';

module.exports = async ({ github, context, core, result }) => {
  const jobs = await github.paginate(github.rest.actions.listJobsForWorkflowRun, {
    ...context.repo,
    run_id: context.runId,
    filter: 'latest',
    per_page: 100,
  });
  const rows = jobs
    .filter(job => job.name.startsWith('composer run '))
    .sort((a, b) => a.name.localeCompare(b.name))
    .map(job => {
      const seconds = job.started_at && job.completed_at
        ? Math.max(0, Math.round((Date.parse(job.completed_at) - Date.parse(job.started_at)) / 1000))
        : null;
      return [
        job.name,
        job.conclusion ?? job.status,
        seconds === null ? '—' : `${Math.floor(seconds / 60)}m ${seconds % 60}s`,
        `<a href="${job.html_url}">View logs</a>`,
      ];
    });

  await core.summary
    .addHeading('CI summary', 2)
    .addRaw(`Overall result: ${result}`, true)
    .addTable([
      ['Check / PHP / Runner', 'Result', 'Duration', 'Logs'].map(data => ({ data, header: true })),
      ...rows,
    ])
    .write();
};
