const icons = {
    image: 'file-image-o',
    pdf: 'file-pdf-o',
    word: 'file-word-o',
    powerpoint: 'file-powerpoint-o',
    excel: 'file-excel-o',
    audio: 'file-audio-o',
    video: 'file-video-o',
    zip: 'file-zip-o',
    code: 'file-code-o',
    file: 'file-o'
};

const extensions = {
    gif: icons.image,
    jpeg: icons.image,
    jpg: icons.image,
    png: icons.image,

    pdf: icons.pdf,

    doc: icons.word,
    docx: icons.word,

    ppt: icons.powerpoint,
    pptx: icons.powerpoint,

    xls: icons.excel,
    xlsx: icons.excel,

    aac: icons.audio,
    mp3: icons.audio,
    ogg: icons.audio,

    avi: icons.video,
    flv: icons.video,
    mkv: icons.video,
    mp4: icons.video,

    gz: icons.zip,
    zip: icons.zip,

    css: icons.code,
    html: icons.code,
    js: icons.code,

    file: icons.file
};

export {extensions, icons};