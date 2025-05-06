import DOMPurify from 'dompurify';

export const parseHTML = (html: string): Element[] => {
  const sanitizedHtml = DOMPurify.sanitize(html);
  const parser = new DOMParser();
  const doc = parser.parseFromString(sanitizedHtml, 'text/html');
  return Array.from(doc.body.children);
}; 