import { observer } from 'mobx-react-lite';
import { useRef, useState } from 'react';
import { templateStore } from '../store/TemplateStore';

interface UploadTabProps {
  onUploaded: () => void;
}

export const UploadTab = observer(function UploadTab({ onUploaded }: UploadTabProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);

  const handleFile = async (file: File | null) => {
    if (!file) {
      return;
    }

    const ok = await templateStore.uploadTemplate(file);
    if (ok) {
      onUploaded();
    }
  };

  return (
    <div className="upload-tab">
      <p className="text-muted mb-4">
        Загрузите Excel-шаблон (.xls или .xlsx). Структура должна содержать колонки:
        модель, размер, цвет, цена, сумма (заказ), описание — как в прайс-листах Mia-Amore.
      </p>

      <div
        className={`upload-dropzone ${dragOver ? 'upload-dropzone--active' : ''}`}
        onDragOver={(event) => {
          event.preventDefault();
          setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(event) => {
          event.preventDefault();
          setDragOver(false);
          void handleFile(event.dataTransfer.files[0] ?? null);
        }}
        onClick={() => inputRef.current?.click()}
        role="button"
        tabIndex={0}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            inputRef.current?.click();
          }
        }}
      >
        <div className="upload-dropzone__icon">📄</div>
        <div className="fw-semibold">Перетащите файл сюда или нажмите для выбора</div>
        <div className="text-muted small">Поддерживаются .xls и .xlsx до 10 МБ</div>
        <input
          ref={inputRef}
          type="file"
          accept=".xls,.xlsx"
          className="d-none"
          onChange={(event) => void handleFile(event.target.files?.[0] ?? null)}
        />
      </div>

      {templateStore.uploading && (
        <div className="alert alert-info mt-3">Загрузка и разбор шаблона…</div>
      )}

      {templateStore.template && (
        <div className="card mt-4">
          <div className="card-body">
            <div className="d-flex justify-content-between align-items-start gap-3">
              <div>
                <h5 className="card-title">Текущий шаблон</h5>
                <p className="mb-1">
                  <strong>Файл:</strong> {templateStore.template.original_name}
                </p>
                <p className="mb-0 text-muted small">
                  Загружен: {new Date(templateStore.template.uploaded_at).toLocaleString('ru-RU')}
                </p>
                {templateStore.form && (
                  <p className="mt-2 mb-0">
                    <strong>Коллекция:</strong> {templateStore.form.title} ·{' '}
                    {templateStore.form.groups.length} групп товаров
                  </p>
                )}
              </div>
              <button
                type="button"
                className="btn btn-outline-danger btn-sm"
                disabled={templateStore.deleting}
                onClick={() => {
                  if (window.confirm('Удалить текущий шаблон?')) {
                    void templateStore.deleteTemplate();
                  }
                }}
              >
                {templateStore.deleting ? 'Удаление…' : 'Удалить шаблон'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
});
