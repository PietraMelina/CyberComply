export type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
};

export type Client = {
  id: string;
  name: string;
  type: "PRIV" | "PUBL";
  is_active: boolean;
};

export type Role = {
  id: number;
  name: string;
  type: "INTERNAL" | "CLIENT";
};

export type User = {
  id: string;
  email: string;
  client_id: string | null;
  role_id: number;
  role?: Role;
  is_active: boolean;
  mfa_enabled: boolean;
  created_at: string;
};

export type Module = {
  id: number;
  client_id: string;
  code: string;
  name: string;
  description?: string | null;
  version: number;
  is_active: boolean;
};

export type Question = {
  id: number;
  module_id: number;
  client_id: string;
  question_text: string;
  weight: number;
  order_index: number;
  is_active: boolean;
};

export type ResponseItem = {
  id: number;
  client_id: string;
  question_id: number;
  site_id: number | null;
  version: number;
  status: "CONFORME" | "PARCIAL" | "NAO_CONFORME" | "NAO_APLICA";
  comment?: string | null;
  answered_by: string;
  answered_at: string;
  previous_version_id: number | null;
  is_current: boolean;
  question?: Question;
};

export type Evidence = {
  id: number;
  client_id: string;
  response_id: number;
  internal_token: string;
  original_filename: string;
  file_size_bytes: number;
  mime_type: string;
  uploaded_by: string;
  uploaded_at: string;
};
