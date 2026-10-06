/**
 * Plain-text exports of an exception, used by the "Copy as Markdown" and "Copy AI prompt"
 * buttons on the panel and on public share pages.
 */

const LOCATION = { file: 'File', line: 'Line', class: 'Class', method: 'Method', full_url: 'URL' };

const json = (value) => '```json\n' + JSON.stringify(value, null, 2) + '\n```\n\n';

const isEmpty = (value) =>
    value === null || value === undefined || value === '' || (typeof value === 'object' && Object.keys(value).length === 0);

function frameLine(frame) {
    if (typeof frame !== 'object' || frame === null) return String(frame);
    const call = frame.function ? `${frame.class ?? ''}${frame.type ?? ''}${frame.function}()` : '';
    const where = frame.file ? `${frame.file}:${frame.line ?? ''}` : '[internal]';
    return call ? `${call} — ${where}` : where;
}

function context(d, { userHeading, storageHeading, additionalHeading }) {
    let out = '';
    const location = Object.entries(LOCATION).filter(([key]) => d[key]);
    if (location.length) {
        out += '## Location\n\n';
        location.forEach(([key, label]) => (out += `- **${label}:** \`${d[key]}\`\n`));
        out += '\n';
    }
    if (Array.isArray(d.executor) && d.executor.length) {
        out += '## Stack Trace\n\n```\n' + d.executor.map(frameLine).join('\n') + '\n```\n\n';
    }
    const http = Object.fromEntries(Object.entries(d.http ?? {}).filter(([, v]) => !isEmpty(v)));
    if (!isEmpty(http)) out += '## HTTP\n\n' + json(http);
    if (!isEmpty(d.additional)) out += `## ${additionalHeading}\n\n` + json(d.additional);
    if (!isEmpty(d.user)) out += `## ${userHeading}\n\n` + json(d.user);
    if (!isEmpty(d.storage)) out += `## ${storageHeading}\n\n` + json(d.storage);
    return out;
}

export function exceptionMarkdown(d) {
    let md = `# Exception: ${d.exception || 'Unknown'}\n\n`;
    if (d.issue_code) md += `**Issue:** ${d.issue_code}  \n`;
    if (d.project) md += `**Project:** ${d.project}  \n`;
    if (d.error) md += `**Error:**\n\`\`\`\n${d.error}\n\`\`\`\n\n`;
    md += `**Date:** ${d.created_at}  \n**Status:** ${d.status}  \n`;
    if (d.host) md += `**Host:** ${d.host}  \n`;
    md += '\n';
    md += context(d, { userHeading: 'User (client)', storageHeading: 'Runtime', additionalHeading: 'Additional Data' });
    return md;
}

export function exceptionPrompt(d) {
    let prompt = 'You are an expert software engineer. I need you to investigate and help me resolve the following exception that occurred in my application.\n\n';
    prompt += 'Please analyze the error, identify the root cause, and provide a clear explanation and concrete fix.\n\n---\n\n';
    prompt += `## Exception\n\n**Type:** \`${d.exception || 'Unknown'}\`\n`;
    if (d.error) prompt += `**Message:**\n\`\`\`\n${d.error}\n\`\`\`\n`;
    prompt += `**Status:** ${d.status}  \n**Date:** ${d.created_at}  \n`;
    if (d.host) prompt += `**Host:** ${d.host}  \n`;
    if (d.storage?.php || d.storage?.laravel) {
        prompt += `**Runtime:** PHP ${d.storage.php ?? '?'} · Laravel ${d.storage.laravel ?? '?'}  \n`;
    }
    prompt += '\n';
    prompt += context(d, { userHeading: 'User (client)', storageHeading: 'Runtime', additionalHeading: 'Additional Context' });
    prompt += '---\n\n## What I need from you\n\n';
    prompt += '1. **Root cause analysis** — explain why this exception is occurring\n';
    prompt += '2. **Step-by-step fix** — provide the code changes or configuration needed to resolve it\n';
    prompt += '3. **Prevention** — suggest how to prevent this type of error in the future\n';
    return prompt;
}
